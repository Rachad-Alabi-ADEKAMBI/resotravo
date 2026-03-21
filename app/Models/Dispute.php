<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispute extends Model
{
    protected $fillable = [
        'mission_id', 'client_id', 'contractor_id', 'admin_id',
        'subject', 'description', 'status', 'verdict', 'verdict_note',
        'contractor_suspended', 'opened_at', 'resolved_at',
    ];

    protected $casts = [
        'contractor_suspended' => 'boolean',
        'opened_at'            => 'datetime',
        'resolved_at'          => 'datetime',
    ];

    const STATUS_LABELS = [
        'open'                 => 'Ouvert',
        'in_progress'          => 'En cours',
        'resolved_client'      => 'Résolu — Client',
        'resolved_contractor'  => 'Résolu — Prestataire',
        'closed'               => 'Clôturé',
    ];

    const VERDICT_LABELS = [
        'client'     => '👤 En faveur du client',
        'contractor' => '👷 En faveur du prestataire',
        'shared'     => '⚖️ Responsabilité partagée',
    ];

    // ── Relations ────────────────────────────────────────────────

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class)->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DisputeAttachment::class)->orderBy('created_at');
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getVerdictLabelAttribute(): string
    {
        return self::VERDICT_LABELS[$this->verdict] ?? '—';
    }

    public function getIsResolvedAttribute(): bool
    {
        return in_array($this->status, ['resolved_client', 'resolved_contractor', 'closed']);
    }
}