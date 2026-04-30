<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'google_id',
        'email_verified_at',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── Relations ────────────────────────────────────────────────────────

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function contractor(): HasOne
    {
        return $this->hasOne(Contractor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Retourne le profil métier selon le rôle.
     * Usage : $user->profile
     */
    public function profile(): HasOne
    {
        return match ($this->role) {
            'client'     => $this->client(),
            'contractor' => $this->contractor(),
            default      => $this->client(),
        };
    }

    // ── Helpers rôle ─────────────────────────────────────────────────────

    public function isAdmin(): bool      { return $this->role === 'admin';      }
    public function isClient(): bool     { return $this->role === 'client';     }
    public function isContractor(): bool { return $this->role === 'contractor'; }
    public function isTalent(): bool     { return $this->role === 'talent';     }

    // ── Certification ─────────────────────────────────────────────────────

    /**
     * Vérifie si tous les documents requis pour son rôle sont approuvés.
     */
    public function isCertified(): bool
    {
        $required = Document::requiredFor($this->role);

        if (empty($required)) return false;

        $approvedTypes = $this->documents()
            ->approved()
            ->pluck('type')
            ->toArray();

        return empty(array_diff($required, $approvedTypes));
    }

    /**
     * Retourne les types de documents non encore soumis pour son rôle.
     */
    public function missingDocuments(): array
    {
        $required  = Document::requiredFor($this->role);
        $submitted = $this->documents()->pluck('type')->toArray();

        return array_diff($required, $submitted);
    }

    /**
     * Retourne la progression des documents pour son rôle.
     * Ex: [ 'approved' => 3, 'total' => 5, 'percentage' => 60 ]
     */
    public function documentProgress(): array
    {
        $required = Document::requiredFor($this->role);
        $total    = count($required);

        if ($total === 0) {
            return ['approved' => 0, 'total' => 0, 'percentage' => 100];
        }

        $approved = $this->documents()
            ->approved()
            ->whereIn('type', $required)
            ->count();

        return [
            'approved'   => $approved,
            'total'      => $total,
            'percentage' => round(($approved / $total) * 100),
        ];
    }
}