<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'category',
        'accreditation',
        'admin_only',
        'is_suggestion',
        'is_visible',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'admin_only'    => 'boolean',
        'is_suggestion' => 'boolean',
        'is_visible'    => 'boolean',
        'sort_order'    => 'integer',
    ];

    // ── Scopes ────────────────────────────────────────────────────

    /** Services actifs et visibles — pour la page d'accueil */
    public function scopeVisible($query)
    {
        return $query->where('status', 'active')
                     ->orderBy('sort_order')
                     ->orderBy('name');
    }

    /** Services actifs (admin catalogue) */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ── Mutator : génère le slug automatiquement si absent ────────

    public static function boot(): void
    {
        parent::boot();

        static::creating(function (self $service) {
            if (empty($service->slug)) {
                $service->slug = Str::slug($service->name);
            }
        });
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getAccredLabelAttribute(): string
    {
        return match ($this->accreditation) {
            'domicile'   => '🏠 Domicile',
            'entreprise' => '🏢 Entreprise',
            'both'       => '🏠🏢 Les deux',
            default      => '—',
        };
    }

    // ── Données de seed (16 services de la page d'accueil) ────────

    public static function seedData(): array
    {
        return [
            [
                'name'        => 'Plomberie',
                'icon'        => '🚿',
                'description' => 'Fuites, canalisations, robinetterie, sanitaires.',
                'category'    => 'Technique',
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Électricité',
                'icon'        => '⚡',
                'description' => 'Installations, pannes, tableaux électriques.',
                'category'    => 'Technique',
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Menuiserie',
                'icon'        => '🪚',
                'description' => 'Portes, fenêtres, meubles sur-mesure, parquet.',
                'category'    => 'Artisanat',
                'sort_order'  => 3,
            ],
            [
                'name'        => 'Ferronnerie',
                'icon'        => '⚙️',
                'description' => 'Portails, grilles de sécurité, escaliers métalliques.',
                'category'    => 'Artisanat',
                'sort_order'  => 4,
            ],
            [
                'name'        => 'Climatisation',
                'icon'        => '❄️',
                'description' => 'Installation, réparation, maintenance clim & froid.',
                'category'    => 'Technique',
                'sort_order'  => 5,
            ],
            [
                'name'        => 'Mécanique Auto',
                'icon'        => '🔩',
                'description' => 'Entretien, réparations, diagnostic véhicules.',
                'category'    => 'Automobile',
                'sort_order'  => 6,
            ],
            [
                'name'        => 'Maintenance',
                'icon'        => '🏗️',
                'description' => 'Petits travaux, réparations diverses à domicile.',
                'category'    => 'Général',
                'sort_order'  => 7,
            ],
            [
                'name'        => 'Nettoyage',
                'icon'        => '🧹',
                'description' => 'Entretien ménager, nettoyage professionnel.',
                'category'    => 'Entretien',
                'sort_order'  => 8,
                'admin_only'  => true,
            ],
            [
                'name'        => 'Vulcanisation',
                'icon'        => '🛞',
                'description' => 'Pneus, chambres à air, équilibrage, jantes.',
                'category'    => 'Automobile',
                'sort_order'  => 9,
            ],
            [
                'name'        => 'Peinture',
                'icon'        => '🖌️',
                'description' => 'Peinture intérieure, extérieure, décoration murale.',
                'category'    => 'Artisanat',
                'sort_order'  => 10,
            ],
            [
                'name'        => 'Vitrerie',
                'icon'        => '🪟',
                'description' => 'Pose et remplacement de vitres et miroirs.',
                'category'    => 'Artisanat',
                'sort_order'  => 11,
            ],
            [
                'name'        => 'Maçonnerie',
                'icon'        => '🧱',
                'description' => 'Construction, rénovation, carrelage, béton.',
                'category'    => 'Gros œuvre',
                'sort_order'  => 12,
            ],
            [
                'name'        => 'Serrurerie',
                'icon'        => '🔐',
                'description' => 'Remplacement de serrures, blindage, coffres-forts.',
                'category'    => 'Sécurité',
                'sort_order'  => 13,
            ],
            [
                'name'        => 'Jardinage',
                'icon'        => '🌿',
                'description' => 'Taille, tonte, aménagement paysager, entretien.',
                'category'    => 'Entretien',
                'sort_order'  => 14,
            ],
            [
                'name'        => 'Piscine',
                'icon'        => '🏊',
                'description' => 'Installation, entretien et traitement de piscines.',
                'category'    => 'Entretien',
                'sort_order'  => 15,
            ],
            [
                'name'        => 'Antenne & TV',
                'icon'        => '📡',
                'description' => 'Installation d\'antennes, paraboles, câblage TV.',
                'category'    => 'Technique',
                'sort_order'  => 16,
            ],
        ];
    }
}
