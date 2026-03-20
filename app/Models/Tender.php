<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tender extends Model
{
    use SoftDeletes;

    const STATUS_PENDING   = 'pending';
    const STATUS_PUBLISHED = 'published';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_CLOSED    = 'closed';

    protected $fillable = [
        'user_id',
        'title',
        'domain',
        'location',
        'duration',
        'budget',
        'deadline',
        'profile_type',
        'description',
        'requirements',
        'status',
        'reject_reason',
        'published_at',
        'candidatures_count',
    ];

    protected $casts = [
        'deadline'           => 'date',
        'published_at'       => 'datetime',
        'candidatures_count' => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(TenderApplication::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
                     ->where('deadline', '>=', now()->toDateString());
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->deadline->isPast();
    }

    public function urgency(): string
    {
        $days = now()->diffInDays($this->deadline, false);
        if ($days < 0)  return 'closed';
        if ($days <= 3) return 'urgent';
        if ($days <= 7) return 'new';
        return 'normal';
    }

    public function urgencyLabel(): string
    {
        return match ($this->urgency()) {
            'urgent' => '🔴 Urgent',
            'new'    => '🔵 Nouveau',
            'closed' => '⚫ Expiré',
            default  => '🟢 Ouvert',
        };
    }

    public function domainIcon(): string
    {
        return match (strtolower($this->domain)) {
            'électricité'          => '⚡',
            'plomberie'            => '🔧',
            'menuiserie'           => '🪵',
            'climatisation'        => '❄️',
            'maçonnerie'           => '🏗️',
            'peinture'             => '🎨',
            'informatique'         => '🖥️',
            'génie civil'          => '🏗️',
            'nettoyage industriel' => '🧹',
            'maintenance'          => '🛠️',
            'serrurerie'           => '🔑',
            default                => '📋',
        };
    }

    public function autoTags(): array
    {
        $tags = [];
        if ($this->urgency() === 'urgent') $tags[] = 'Urgent';
        if ($this->urgency() === 'new')    $tags[] = 'Nouveau';
        if (in_array($this->profile_type, ['talent', 'both'])) $tags[] = 'Talent requis';
        return $tags;
    }
}