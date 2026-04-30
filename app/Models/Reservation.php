<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = ['mission_id', 'day', 'time'];

    protected $casts = [
        'day' => 'date',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
