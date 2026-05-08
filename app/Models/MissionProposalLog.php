<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionProposalLog extends Model
{
    protected $fillable = [
        'mission_id',
        'mission_proposal_id',
        'contractor_user_id',
        'contractor_id',
        'event',
        'status',
        'proposed_at',
        'expires_at',
        'responded_at',
        'reason',
        'meta',
    ];

    protected $casts = [
        'proposed_at'  => 'datetime',
        'expires_at'   => 'datetime',
        'responded_at' => 'datetime',
        'meta'         => 'array',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(MissionProposal::class, 'mission_proposal_id');
    }

    public function contractorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_user_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }
}
