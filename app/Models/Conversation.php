<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Conversation extends Model
{
    protected $fillable = [
        'type',
        'mission_id',
        'title',
        'last_message',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'conversation_id', 'user_id')
            ->withPivot('role', 'last_read_at')
            ->withTimestamps();
    }

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * Nombre de messages non lus pour un utilisateur donné.
     */
    public function unreadCountFor(int $userId): int
    {
        $participant = $this->participants()
            ->where('user_id', $userId)
            ->first();

        if (!$participant) return 0;

        $lastRead = $participant->pivot->last_read_at;

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->when($lastRead, fn($q) => $q->where('created_at', '>', $lastRead))
            ->count();
    }

    /**
     * Récupère ou crée la conversation liée à une mission.
     * Ajoute automatiquement client et prestataire comme participants.
     */
    public static function forMission(Mission $mission): self
    {
        $conversation = static::where('mission_id', $mission->id)
            ->where('type', 'mission')
            ->first();

        if ($conversation) return $conversation;

        $conversation = static::create([
            'type'       => 'mission',
            'mission_id' => $mission->id,
            'title'      => "Mission : {$mission->service}",
        ]);

        // Ajouter le client
        if ($mission->client?->user_id) {
            $conversation->participants()->attach($mission->client->user_id, ['role' => 'client']);
        }

        // Ajouter le prestataire
        if ($mission->contractor?->user_id) {
            $conversation->participants()->attach($mission->contractor->user_id, ['role' => 'contractor']);
        }

        return $conversation;
    }
}