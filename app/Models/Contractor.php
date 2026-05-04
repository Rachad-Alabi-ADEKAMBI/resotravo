<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contractor extends Model
{
    protected $fillable = [
        'user_id', 'service_id', 'first_name', 'last_name', 'phone',
        'address', 'city', 'profile_picture',
        'specialty', 'specialties', 'intervention_zone',
        'experience_years', 'bio', 'accreditation',
        'available', 'start_time', 'end_time', 'working_days',
        'total_missions', 'completed_missions',
        'average_rating', 'reviews_count',
        'latitude', 'longitude', 'radius_km',
        'diagnostic_fee',
        'ifu',
    ];

    protected $casts = [
        'specialties'    => 'array',
        'working_days'   => 'array',
        'available'      => 'boolean',
        'average_rating'  => 'decimal:2',
        'diagnostic_fee'  => 'decimal:2',
    ];

    public static array $accreditations = [
        'none'     => 'Aucune',
        'home'     => 'Domicile',
        'business' => 'Entreprise',
        'both'     => 'Domicile + Entreprise',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }

    public function getAccreditationLabelAttribute(): string
    {
        return self::$accreditations[$this->accreditation] ?? 'Aucune';
    }

    public function getCompletionRateAttribute(): int
    {
        if ($this->total_missions === 0) return 0;
        return (int) round(($this->completed_missions / $this->total_missions) * 100);
    }

    public function canWorkAt(string $type): bool
    {
        return match ($type) {
            'home'     => in_array($this->accreditation, ['home', 'both']),
            'business' => in_array($this->accreditation, ['business', 'both']),
            default    => false,
        };
    }

    // Scopes
    public function scopeAvailable($query)                         { return $query->where('available', true); }
    public function scopeBySpecialty($query, string $s)            { return $query->where('specialty', $s); }
    public function scopeByCity($query, string $city)              { return $query->where('city', $city); }
    public function scopeAccreditedHome($query)                    { return $query->whereIn('accreditation', ['home', 'both']); }
    public function scopeAccreditedBusiness($query)                { return $query->whereIn('accreditation', ['business', 'both']); }
    public function scopeBestRated($query)                         { return $query->orderByDesc('average_rating'); }

    // Nearby via Haversine
    public function scopeNearby($query, float $lat, float $lng, int $km = 10)
    {
        return $query->selectRaw("*, (6371 * acos(
            cos(radians(?)) * cos(radians(latitude))
            * cos(radians(longitude) - radians(?))
            + sin(radians(?)) * sin(radians(latitude))
        )) AS distance", [$lat, $lng, $lat])
        ->having('distance', '<=', $km)
        ->orderBy('distance');
    }
}
