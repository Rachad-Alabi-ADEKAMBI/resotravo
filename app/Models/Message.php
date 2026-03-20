<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'attachment_path',
        'attachment_name',
        'attachment_type',
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ── Accessors ────────────────────────────────────────────────

    /**
     * URL publique de la pièce jointe.
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_path) return null;
        return Storage::disk('public')->url($this->attachment_path);
    }

    // ── Format JSON pour le frontend ─────────────────────────────

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id'       => $this->sender_id,
            'sender_name'     => $this->sender?->name ?? 'Utilisateur',
            'sender_role'     => $this->sender?->role ?? '',
            'body'            => $this->body,
            'type'            => $this->type,
            'attachment_url'  => $this->attachment_url,
            'attachment_name' => $this->attachment_name,
            'attachment_type' => $this->attachment_type,
            'created_at'      => $this->created_at->toISOString(),
            'ago'             => $this->created_at->locale('fr')->diffForHumans(),
        ];
    }
}