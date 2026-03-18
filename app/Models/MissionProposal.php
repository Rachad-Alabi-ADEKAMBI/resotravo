<?php

namespace App\Models;

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
        $this->update([
            'status'       => 'accepted',
            'responded_at' => now(),
        ]);

        // Assigner la mission à ce prestataire
        $this->mission->update([
            'contractor_id' => $this->contractor_id,
            'status'        => 'accepted',
        ]);

        // Annuler toutes les autres propositions pending sur cette mission
        static::where('mission_id', $this->mission_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'pending')
            ->update([
                'status'       => 'superseded',
                'responded_at' => now(),
            ]);
    }

    public function reject(?string $reason = null): void
    {
        $this->update([
            'status'        => 'rejected',
            'reject_reason' => $reason,
            'responded_at'  => now(),
        ]);

        // Si plus aucune proposition pending sur cette mission → repasser en pending
        $remainingPending = static::where('mission_id', $this->mission_id)
            ->where('status', 'pending')
            ->exists();

        if (!$remainingPending) {
            $this->mission->update(['status' => 'pending']);
        }
    }
}