<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'filename',
        'path',
        'disk',
        'status',
        'reject_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // ── Documents requis par rôle ────────────────────────────────────────

    /**
     * Tous les types de documents existants avec leur label et icône.
     */
    public static array $types = [
        // Prestataire (contractor)
        'cip'         => ['label' => "Certificat d'Identification Personnelle (CIP) — CNI / Passeport", 'icon' => '🪪'],
        'photo'       => ['label' => "Photo d'identité professionnelle",                                'icon' => '📷'],
        'attestation' => ['label' => "Attestation de résidence (moins de 3 mois)",                     'icon' => '📄'],
        'casier'      => ['label' => "Casier judiciaire vierge (Bulletin n°3)",                        'icon' => '⚖️'],
        'diplome'     => ['label' => "Diplôme ou attestation de qualification",                        'icon' => '🎓'],

        // Talent (BAC+3 minimum)
        'diplome_sup' => ['label' => "Diplôme supérieur (BAC+3 minimum)",                             'icon' => '🎓'],
        'cv'          => ['label' => "Curriculum Vitae (CV)",                                          'icon' => '📋'],
        'certification' => ['label' => "Certificat(s) professionnel(s)",                              'icon' => '🏅'],
    ];

    /**
     * Retourne les types de documents requis selon le rôle.
     */
    public static function requiredFor(string $role): array
    {
        return match ($role) {
            'contractor' => ['cip', 'photo', 'attestation', 'casier', 'diplome'],
            'talent'     => ['cip', 'photo', 'diplome_sup', 'cv'],
            'client'     => [],   // aucun document requis
            'admin'      => [],   // aucun document requis
            default      => [],
        };
    }

    /**
     * Rétrocompatibilité — types requis pour les prestataires.
     * @deprecated  Utiliser Document::requiredFor($role) à la place.
     */
    public static array $required = [
        'cip'         => "Certificat d'Identification Personnelle (CIP) — CNI / Passeport",
        'photo'       => "Photo d'identité professionnelle",
        'attestation' => "Attestation de résidence (moins de 3 mois)",
        'casier'      => "Casier judiciaire vierge (Bulletin n°3)",
        'diplome'     => "Diplôme ou attestation de qualification",
    ];

    // ── Relations ───────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ── Accesseurs ──────────────────────────────────────────────────────

    // Label lisible du type
    public function getLabelAttribute(): string
    {
        return self::$types[$this->type]['label'] ?? $this->type;
    }

    // Icône selon le type
    public function getIconAttribute(): string
    {
        return self::$types[$this->type]['icon'] ?? '📎';
    }

    // Statut lisible
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'  => 'En attente',
            'approved' => 'Validé',
            'rejected' => 'Refusé',
            'missing'  => 'Manquant',
            default    => $this->status,
        };
    }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }

    // ── Scopes ──────────────────────────────────────────────────────────

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

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}