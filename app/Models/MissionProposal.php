<?php

namespace App\Models;

use App\Notifications\AppNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionProposal extends Model
{
    protected $fillable = [
        'mission_id',
        'contractor_id',
        'status',
        'proposed_at',
        'expires_at',
        'responded_at',
        'reject_reason',
    ];

    protected $casts = [
        'proposed_at'  => 'datetime',
        'expires_at'   => 'datetime',
        'responded_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_id');
    }

    public function contractorRecord(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id', 'user_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'accepted']);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<', now());
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && $this->status === 'pending';
    }

    public function accept(): void
    {
        $respondedAt = now();

        $this->update([
            'status'       => 'accepted',
            'responded_at' => $respondedAt,
        ]);

        // contractor_id dans MissionProposal est un user_id (table users)
        // mission->contractor_id doit être l'id de la table contractors
        $contractorRecord = \App\Models\Contractor::where('user_id', $this->contractor_id)->first();

        // Assigner la mission à ce prestataire
        $this->mission->update([
            'contractor_id' => $contractorRecord?->id ?? $this->contractor_id,
            'status'        => 'accepted',
            'accepted_at'   => $respondedAt,
        ]);

        $this->logEvent('accepted', 'accepted', respondedAt: $respondedAt);

        // Annuler toutes les autres propositions pending sur cette mission
        static::where('mission_id', $this->mission_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'pending')
            ->get()
            ->each(fn (self $proposal) => $proposal->markSuperseded($respondedAt));
    }

    public function reject(?string $reason = null): void
    {
        $respondedAt = now();

        $this->update([
            'status'        => 'rejected',
            'reject_reason' => $reason,
            'responded_at'  => $respondedAt,
        ]);

        $this->logEvent('rejected', 'rejected', $reason, $respondedAt);
        $this->notifyRejected($reason);

        // Si plus aucune proposition pending sur cette mission → repasser en pending
        $remainingPending = static::where('mission_id', $this->mission_id)
            ->where('status', 'pending')
            ->exists();

        if (!$remainingPending) {
            $this->mission->update(['status' => 'pending']);
        }
    }

    public function expire(?string $reason = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $respondedAt = now();
        $reason ??= "Aucune réponse dans le délai de 5 minutes.";

        $this->update([
            'status'        => 'expired',
            'responded_at'  => $respondedAt,
            'reject_reason' => $reason,
        ]);

        $this->logEvent('expired', 'expired', $reason, $respondedAt);
        $this->notifyExpired();

        if (! static::where('mission_id', $this->mission_id)->where('status', 'pending')->exists()) {
            $this->mission->update(['status' => Mission::STATUS_PENDING]);
        }

        return true;
    }

    public function markSuperseded($respondedAt = null): void
    {
        $respondedAt ??= now();

        $this->update([
            'status'       => 'superseded',
            'responded_at' => $respondedAt,
        ]);

        $this->logEvent(
            'superseded',
            'superseded',
            'Un autre prestataire a accepté la mission avant ce prestataire.',
            $respondedAt
        );
    }

    public function logEvent(string $event, ?string $status = null, ?string $reason = null, $respondedAt = null, array $meta = []): void
    {
        $contractorRecord = Contractor::where('user_id', $this->contractor_id)->first();

        MissionProposalLog::create([
            'mission_id'          => $this->mission_id,
            'mission_proposal_id' => $this->id,
            'contractor_user_id'  => $this->contractor_id,
            'contractor_id'       => $contractorRecord?->id,
            'event'               => $event,
            'status'              => $status ?? $this->status,
            'proposed_at'         => $this->proposed_at,
            'expires_at'          => $this->expires_at,
            'responded_at'        => $respondedAt ?? $this->responded_at,
            'reason'              => $reason,
            'meta'                => $meta ?: null,
        ]);
    }

    private function notifyExpired(): void
    {
        $mission = $this->mission;
        $contractorUser = $this->contractor;
        $contractorName = $contractorUser?->name ?? 'Ce prestataire';

        $contractorUser?->notify(new AppNotification(
            event: 'mission.proposal_expired',
            title: 'Délai de réponse dépassé',
            body: "Vous n'avez pas accepté la mission « {$mission->service} » dans le délai imparti. Elle sera proposée à un autre prestataire.",
            url: "/contractor/missions/{$mission->id}",
            icon: 'clock',
            extra: ['mission_id' => $mission->id, 'proposal_id' => $this->id],
        ));

        User::where('role', 'admin')->each(fn (User $admin) => $admin->notify(new AppNotification(
            event: 'mission.proposal_expired_admin',
            title: 'Proposition non acceptée',
            body: "{$contractorName} n'a pas accepté la mission « {$mission->service} » dans les 5 minutes. Proposez-la à un autre prestataire.",
            url: "/admin/missions?id={$mission->id}",
            icon: 'clock',
            extra: ['mission_id' => $mission->id, 'proposal_id' => $this->id, 'contractor_user_id' => $this->contractor_id],
        )));
    }

    private function notifyRejected(?string $reason = null): void
    {
        $mission = $this->mission;
        $contractorUser = $this->contractor;
        $contractorName = $contractorUser?->name ?? 'Ce prestataire';
        $reasonText = $reason ? " Motif : {$reason}" : '';

        User::where('role', 'admin')->each(fn (User $admin) => $admin->notify(new AppNotification(
            event: 'mission.proposal_rejected_admin',
            title: 'Mission refusée par un prestataire',
            body: "{$contractorName} a refusé la mission « {$mission->service} ».{$reasonText}",
            url: "/admin/missions?id={$mission->id}",
            icon: 'x-circle',
            extra: [
                'mission_id' => $mission->id,
                'proposal_id' => $this->id,
                'contractor_user_id' => $this->contractor_id,
                'reason' => $reason,
            ],
        )));
    }
}
