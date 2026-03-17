<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\MissionQuote;

class MissionQuoteItem extends Model
{
    const TYPE_PART   = 'part';
    const TYPE_LABOR  = 'labor';
    const TYPE_TRAVEL = 'travel';
    const TYPE_OTHER  = 'other';

    protected $table = 'mission_quote_items';

    protected $fillable = [
        'quote_id',
        'type',
        'description',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total'      => 'decimal:2', // storedAs — read-only
    ];

    // ── Relations ────────────────────────────────────────────────

    public function quote(): BelongsTo
    {
        return $this->belongsTo(MissionQuote::class, 'quote_id');
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function getLineTotalAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}