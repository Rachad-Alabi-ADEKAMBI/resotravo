<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Mission extends Model
{
    use HasFactory, SoftDeletes;

    // ══════════════════════════════════════════════════════════════
    // STATUS CONSTANTS — 12-step workflow
    // ══════════════════════════════════════════════════════════════

    const STATUS_PENDING          = 'pending';
    const STATUS_ASSIGNED         = 'assigned';
    const STATUS_ACCEPTED         = 'accepted';
    const STATUS_CONTACT_MADE     = 'contact_made';
    const STATUS_ON_THE_WAY       = 'on_the_way';
    const STATUS_TRACKING         = 'tracking';
    const STATUS_IN_PROGRESS      = 'in_progress';
    const STATUS_QUOTE_SUBMITTED  = 'quote_submitted';
    const STATUS_ORDER_PLACED     = 'order_placed';
    const STATUS_AWAITING_CONFIRM = 'awaiting_confirm';
    const STATUS_COMPLETED        = 'completed';
    const STATUS_CLOSED           = 'closed';
    const STATUS_CANCELLED        = 'cancelled'; // annulation admin ou litige

    /**
     * Allowed transitions.
     * Key = current status → Value = allowed next status(es).
     */
    const TRANSITIONS = [
        self::STATUS_PENDING          => [self::STATUS_ASSIGNED,                                    self::STATUS_CANCELLED],
        self::STATUS_ASSIGNED         => [self::STATUS_ACCEPTED,         self::STATUS_PENDING,      self::STATUS_CANCELLED],
        self::STATUS_ACCEPTED         => [self::STATUS_CONTACT_MADE,     self::STATUS_ON_THE_WAY,   self::STATUS_CANCELLED],
        self::STATUS_CONTACT_MADE     => [self::STATUS_ON_THE_WAY,                                  self::STATUS_CANCELLED],
        self::STATUS_ON_THE_WAY       => [self::STATUS_TRACKING,         self::STATUS_IN_PROGRESS,  self::STATUS_CANCELLED],
        self::STATUS_TRACKING         => [self::STATUS_IN_PROGRESS,                                 self::STATUS_CANCELLED],
        self::STATUS_IN_PROGRESS      => [self::STATUS_QUOTE_SUBMITTED,                             self::STATUS_CANCELLED],
        self::STATUS_QUOTE_SUBMITTED  => [self::STATUS_ORDER_PLACED,     self::STATUS_QUOTE_SUBMITTED], // revision allowed
        self::STATUS_ORDER_PLACED     => [self::STATUS_AWAITING_CONFIRM,                            self::STATUS_CANCELLED],
        self::STATUS_AWAITING_CONFIRM => [self::STATUS_COMPLETED,                                   self::STATUS_CANCELLED],
        self::STATUS_COMPLETED        => [self::STATUS_CLOSED],
        self::STATUS_CLOSED           => [],
        self::STATUS_CANCELLED        => [self::STATUS_PENDING],         // réouverture admin possible
    ];

    /** Human-readable labels for frontend display */
    const STATUS_LABELS = [
        self::STATUS_PENDING          => 'En attente',
        self::STATUS_ASSIGNED         => 'Proposée',
        self::STATUS_ACCEPTED         => 'Acceptée',
        self::STATUS_CONTACT_MADE     => 'Contact établi',
        self::STATUS_ON_THE_WAY       => 'En route',
        self::STATUS_TRACKING         => 'Suivi en cours',
        self::STATUS_IN_PROGRESS      => 'Intervention en cours',
        self::STATUS_QUOTE_SUBMITTED  => 'Devis soumis',
        self::STATUS_ORDER_PLACED     => 'Commande passée',
        self::STATUS_AWAITING_CONFIRM => 'Att. confirmation',
        self::STATUS_COMPLETED        => 'Terminée',
        self::STATUS_CLOSED           => 'Clôturée',
        self::STATUS_CANCELLED        => 'Annulée',
    ];

    /** Step number (1–12) used by the frontend timeline */
    const STATUS_STEPS = [
        self::STATUS_PENDING          => 1,
        self::STATUS_ASSIGNED         => 2,
        self::STATUS_ACCEPTED         => 3,
        self::STATUS_CONTACT_MADE     => 4,
        self::STATUS_ON_THE_WAY       => 5,
        self::STATUS_TRACKING         => 6,
        self::STATUS_IN_PROGRESS      => 7,
        self::STATUS_QUOTE_SUBMITTED  => 8,
        self::STATUS_ORDER_PLACED     => 9,
        self::STATUS_AWAITING_CONFIRM => 10,
        self::STATUS_COMPLETED        => 11,
        self::STATUS_CLOSED           => 12,
        self::STATUS_CANCELLED        => 0,
    ];

    // ══════════════════════════════════════════════════════════════
    // FILLABLE & CASTS
    // ══════════════════════════════════════════════════════════════

    protected $fillable = [
        'client_id',
        'contractor_id',
        'status',
        'service',
        'address',
        'latitude',
        'longitude',
        'description',
        'images',
        'availabilities',
        'location_type',
        'min_distance_m',
        'assigned_at',
        'accepted_at',
        'arrived_at',
        'completed_at',
        'total_amount',
        'commission',
        'momo_transaction_id',
        'paid_at',
        'invoice_path',
        'reported_issue',
        'dispute_open',
        'cancel_reason',
        'cancelled_at',
    ];

    // Accessors automatiquement inclus dans toArray() / JSON
    protected $appends = ['step', 'status_label'];

    protected $casts = [
        'availabilities' => 'array',
        'dispute_open'   => 'boolean',
        'assigned_at'    => 'datetime',
        'accepted_at'    => 'datetime',
        'arrived_at'     => 'datetime',
        'completed_at'   => 'datetime',
        'paid_at'        => 'datetime',
        'cancelled_at'   => 'datetime',
        'total_amount'   => 'decimal:2',
        'commission'     => 'decimal:2',
        'latitude'       => 'decimal:7',
        'longitude'      => 'decimal:7',
        'min_distance_m' => 'integer',
    ];

    // ══════════════════════════════════════════════════════════════
    // ACCESSORS
    // ══════════════════════════════════════════════════════════════

    protected function images(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => collect(json_decode($value, true) ?? [])
                ->map(fn ($p) => Storage::disk('public')->url($p))
                ->values()
                ->toArray(),
            set: fn ($value) => is_array($value) ? json_encode($value) : $value,
        );
    }

    // ══════════════════════════════════════════════════════════════
    // RELATIONS
    // ══════════════════════════════════════════════════════════════

    /**
     * The client who created the mission.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * The contractor assigned to the mission.
     */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }

    /**
     * The latest quote for this mission.
     */
    public function quote(): HasOne
    {
        return $this->hasOne(MissionQuote::class)->latestOfMany();
    }

    /**
     * Toutes les propositions envoyées à des prestataires pour cette mission.
     * Utilisé par AdminMissionComponent pour la logique multi-proposition.
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(MissionProposal::class);
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(Reservation::class);
    }

    /**
     * Propositions encore en attente de réponse.
     */
    public function pendingProposals(): HasMany
    {
        return $this->hasMany(MissionProposal::class)->where('status', 'pending');
    }

    /**
     * Proposition acceptée (il ne peut y en avoir qu'une).
     */
    public function acceptedProposal(): HasOne
    {
        return $this->hasOne(MissionProposal::class)->where('status', 'accepted');
    }

    // ══════════════════════════════════════════════════════════════
    // SCOPES
    // ══════════════════════════════════════════════════════════════

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForContractor($query, int $contractorId)
    {
        return $query->where('contractor_id', $contractorId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_CANCELLED]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /** Missions sans prestataire assigné et non terminées — pour l'alerte admin */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('contractor_id')
            ->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_CANCELLED, self::STATUS_COMPLETED]);
    }

    // ══════════════════════════════════════════════════════════════
    // BUSINESS METHODS
    // ══════════════════════════════════════════════════════════════

    /**
     * Check whether the transition to the given status is allowed.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? []);
    }

    /**
     * Perform the status transition with automatic timestamps.
     * Throws DomainException if the transition is not allowed.
     */
    public function transitionTo(string $newStatus, ?string $cancelReason = null): self
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Forbidden transition: {$this->status} → {$newStatus}"
            );
        }

        $this->status = $newStatus;

        match ($newStatus) {
            self::STATUS_ASSIGNED    => $this->assigned_at  = now(),
            self::STATUS_ACCEPTED    => $this->accepted_at  = now(),
            self::STATUS_ON_THE_WAY  => $this->accepted_at  = $this->accepted_at ?? now(), // préserver accepted_at
            self::STATUS_IN_PROGRESS => $this->arrived_at   = now(),
            self::STATUS_COMPLETED   => $this->completed_at = now(),
            self::STATUS_CLOSED      => $this->paid_at      = now(),
            self::STATUS_CANCELLED   => $this->cancelled_at = now(),
            default                  => null,
        };

        if ($newStatus === self::STATUS_CANCELLED && $cancelReason) {
            $this->cancel_reason = $cancelReason;
        }

        // Si on remet en pending (réouverture admin) → effacer l'assignation
        if ($newStatus === self::STATUS_PENDING) {
            $this->contractor_id = null;
            $this->cancel_reason = null;
            $this->cancelled_at  = null;
        }

        $this->save();

        return $this;
    }

    /**
     * Annulation admin avec motif — raccourci de transitionTo.
     * Invalide aussi toutes les propositions en attente.
     */
    public function cancelByAdmin(string $reason): self
    {
        $this->transitionTo(self::STATUS_CANCELLED, $reason);

        // Invalider toutes les propositions pending liées à cette mission
        $this->proposals()
            ->where('status', 'pending')
            ->update(['status' => 'superseded', 'responded_at' => now()]);

        return $this;
    }

    /**
     * Current step number (1–12) for the frontend timeline.
     * Retourne 0 pour les missions annulées.
     */
    public function getStepAttribute(): int
    {
        return self::STATUS_STEPS[$this->status] ?? 1;
    }

    /**
     * Human-readable label for the current status (en français).
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Is the contractor currently en route? (GPS active)
     */
    public function isOnTheWay(): bool
    {
        return in_array($this->status, [
            self::STATUS_ON_THE_WAY,
            self::STATUS_TRACKING,
        ]);
    }

    /**
     * Is the payment tab unlocked?
     * Only after the client confirms end of work.
     */
    public function paymentUnlocked(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Calculate and store the Mesotravo commission from quote line items.
     * Uses configurable rates per item type (diagnostic vs main d'oeuvre/other).
     */
    public function calculateCommission(): void
    {
        if (! $this->total_amount) {
            return;
        }

        $rateDiag   = ((float) Setting::get('commission_diagnostic', 10)) / 100;
        $rateLabor  = ((float) Setting::get('commission_main_oeuvre', 10)) / 100;

        $quote = $this->quote;

        if ($quote) {
            $diagTotal  = $quote->items()->where('type', 'diagnostic')->sum(\DB::raw('quantity * unit_price'));
            $laborTotal = $quote->items()->where('type', 'labor')->sum(\DB::raw('quantity * unit_price'));
            $otherTotal = $this->total_amount - $diagTotal - $laborTotal;

            $this->commission = round(
                ($diagTotal * $rateDiag) + ($laborTotal * $rateLabor) + ($otherTotal * $rateLabor),
                2
            );
        } else {
            $this->commission = round($this->total_amount * $rateLabor, 2);
        }

        $this->save();
    }

    /**
     * Vérifie si la mission peut encore recevoir des propositions de prestataires.
     */
    public function canReceiveProposals(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ASSIGNED,
        ]);
    }
}