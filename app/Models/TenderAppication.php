<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderApplication extends Model
{
    const STATUS_PENDING  = 'pending';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_SELECTED = 'selected';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tender_id',
        'user_id',
        'motivation',
        'tarif',
        'disponibilite',
        'status',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => "En cours d'examen",
            self::STATUS_REVIEWED => 'Examinée',
            self::STATUS_SELECTED => 'Sélectionné',
            self::STATUS_REJECTED => 'Non retenu',
            default               => $this->status,
        };
    }

    public function statusIcon(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => '🔄',
            self::STATUS_REVIEWED => '👁️',
            self::STATUS_SELECTED => '✅',
            self::STATUS_REJECTED => '❌',
            default               => '📋',
        };
    }
}