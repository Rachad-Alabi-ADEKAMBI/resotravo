<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsultingTicket extends Model
{
    protected $fillable = [
        'user_id', 'agent_id', 'subject', 'category',
        'status', 'ia_message_count', 'human_requested',
        'human_assigned', 'admin_note', 'rating',
        'first_message_at', 'assigned_at', 'resolved_at',
    ];

    protected $casts = [
        'human_requested'  => 'boolean',
        'human_assigned'   => 'boolean',
        'first_message_at' => 'datetime',
        'assigned_at'      => 'datetime',
        'resolved_at'      => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConsultingMessage::class, 'ticket_id');
    }

    public function lastMessage()
    {
        return $this->hasOne(ConsultingMessage::class, 'ticket_id')->latestOfMany();
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'pending_human', 'in_progress']);
    }

    public function scopePendingHuman($query)
    {
        return $query->where('status', 'pending_human');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    // ── Helpers ──────────────────────────────────────────────────

    const STATUS_LABELS = [
        'open'          => 'Nouveau',
        'ai_handled'    => 'Géré par IA',
        'pending_human' => 'En attente agent',
        'in_progress'   => 'En cours',
        'resolved'      => 'Résolu',
        'closed'        => 'Fermé',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getUnreadCountAttribute(): int
    {
        return $this->messages()->where('sender_type', 'user')->where('is_read', false)->count();
    }
}