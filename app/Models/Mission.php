<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * Allowed transitions.
     * Key = current status → Value = allowed next status(es).
     */
    const TRANSITIONS = [
        self::STATUS_PENDING          => [self::STATUS_ASSIGNED],
        self::STATUS_ASSIGNED         => [self::STATUS_ACCEPTED],
        self::STATUS_ACCEPTED         => [self::STATUS_CONTACT_MADE],
        self::STATUS_CONTACT_MADE     => [self::STATUS_ON_THE_WAY],
        self::STATUS_ON_THE_WAY       => [self::STATUS_TRACKING],
        self::STATUS_TRACKING         => [self::STATUS_IN_PROGRESS],
        self::STATUS_IN_PROGRESS      => [self::STATUS_QUOTE_SUBMITTED],
        self::STATUS_QUOTE_SUBMITTED  => [self::STATUS_ORDER_PLACED, self::STATUS_QUOTE_SUBMITTED], // revision allowed
        self::STATUS_ORDER_PLACED     => [self::STATUS_AWAITING_CONFIRM],
        self::STATUS_AWAITING_CONFIRM => [self::STATUS_COMPLETED],
        self::STATUS_COMPLETED        => [self::STATUS_CLOSED],
        self::STATUS_CLOSED           => [],
    ];

    /** Human-readable labels for frontend display */
    const STATUS_LABELS = [
        self::STATUS_PENDING          => 'Pending',
        self::STATUS_ASSIGNED         => 'Assigned',
        self::STATUS_ACCEPTED         => 'Accepted',
        self::STATUS_CONTACT_MADE     => 'Contact made',
        self::STATUS_ON_THE_WAY       => 'On the way',
        self::STATUS_TRACKING         => 'Tracking',
        self::STATUS_IN_PROGRESS      => 'In progress',
        self::STATUS_QUOTE_SUBMITTED  => 'Quote submitted',
        self::STATUS_ORDER_PLACED     => 'Order placed',
        self::STATUS_AWAITING_CONFIRM => 'Awaiting confirmation',
        self::STATUS_COMPLETED        => 'Completed',
        self::STATUS_CLOSED           => 'Closed',
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
    ];

    // ══════════════════════════════════════════════════════════════
    // FILLABLE & CASTS
    // ══════════════════════════════════════════════════════════════

    protected $fillable = [
        'client_id',        // → clients.id
        'contractor_id',    // → contractors.id
        'status',
        'service',
        'address',
        'latitude',
        'longitude',
        'description',
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
    ];

    protected $casts = [
        'availabilities' => 'array',
        'dispute_open'   => 'boolean',
        'assigned_at'    => 'datetime',
        'accepted_at'    => 'datetime',
        'arrived_at'     => 'datetime',
        'completed_at'   => 'datetime',
        'paid_at'        => 'datetime',
        'total_amount'   => 'decimal:2',
        'commission'     => 'decimal:2',
        'latitude'       => 'decimal:7',
        'longitude'      => 'decimal:7',
        'min_distance_m' => 'integer',
    ];

    // ══════════════════════════════════════════════════════════════
    // RELATIONS
    // ══════════════════════════════════════════════════════════════

    /**
     * The client who created the mission.
     * FK client_id → clients.id
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * The contractor assigned to the mission.
     * FK contractor_id → contractors.id
     */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }

    /**
     * The latest quote for this mission.
     * One mission can have multiple quote versions; we always use the latest.
     */
    public function quote(): HasOne
    {
        return $this->hasOne(MissionQuote::class)->latestOfMany();
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
        return $query->whereNotIn('status', [self::STATUS_CLOSED]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
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
    public function transitionTo(string $newStatus): self
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Forbidden transition: {$this->status} → {$newStatus}"
            );
        }

        $this->status = $newStatus;

        // Auto-timestamps on key transitions
        match ($newStatus) {
            self::STATUS_ASSIGNED    => $this->assigned_at  = now(),
            self::STATUS_ACCEPTED    => $this->accepted_at  = now(),
            self::STATUS_IN_PROGRESS => $this->arrived_at   = now(),
            self::STATUS_COMPLETED   => $this->completed_at = now(),
            self::STATUS_CLOSED      => $this->paid_at      = now(),
            default                  => null,
        };

        $this->save();

        return $this;
    }

    /**
     * Current step number (1–12) for the frontend timeline.
     */
    public function getStepAttribute(): int
    {
        return self::STATUS_STEPS[$this->status] ?? 1;
    }

    /**
     * Human-readable label for the current status.
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
     * Calculate and store the 10% Resotravo commission from total_amount.
     */
    public function calculateCommission(): void
    {
        if ($this->total_amount) {
            $this->commission = round($this->total_amount * 0.10, 2);
            $this->save();
        }
    }
}