<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TalentApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'phone',
        'title',
        'domain',
        'level',
        'exp',
        'bio',
        'availability',
        'linkedin',
        'portfolio',
        'diplomas',
        'certifications',
        'experiences',
        'status',
        'reject_reason',
        'user_id',
    ];

    protected $casts = [
        'diplomas'       => 'array',
        'certifications' => 'array',
        'experiences'    => 'array',
        'exp'            => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────────

    /**
     * L'utilisateur créé lors de la validation de la demande (nullable tant que pending).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // ── Accesseurs ───────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function getAvailabilityLabelAttribute(): string
    {
        return match ($this->availability) {
            'immediate' => 'Disponible immédiatement',
            'parttime'  => 'Temps partiel',
            'mission'   => 'Mission spécifique',
            default     => $this->availability,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'  => 'En attente',
            'approved' => 'Approuvé',
            'rejected' => 'Refusé',
            default    => $this->status,
        };
    }
}