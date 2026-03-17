<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MissionQuote extends Model
{
    // ── Status constants ─────────────────────────────────────────
    const STATUS_DRAFT     = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_REVISED   = 'revised';

    protected $table = 'mission_quotes';

    protected $fillable = [
        'mission_id',
        'diagnosis',
        'amount_excl_tax',
        'amount_incl_tax',
        'status',
        'rejection_reason',
        'version',
    ];

    protected $casts = [
        'amount_excl_tax' => 'decimal:2',
        'amount_incl_tax' => 'decimal:2',
        'version'         => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────────

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MissionQuoteItem::class, 'quote_id');
    }

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * Recalculate totals from line items and save.
     */
    public function recalculateTotals(): void
    {
        $total = $this->items()->sum(\DB::raw('quantity * unit_price'));
        $this->update([
            'amount_excl_tax' => $total,
            'amount_incl_tax' => $total, // TVA à adapter si besoin
        ]);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}