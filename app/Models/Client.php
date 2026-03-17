<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'phone',
        'address', 'city', 'profile_picture',
        'account_type', 'company_name', 'business_sector',
        'min_distance_m', 'total_missions', 'completed_missions',
        'saved_addresses',
    ];

    protected $casts = [
        'saved_addresses' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function getDisplayNameAttribute(): string
    {
        return $this->isCompany() && $this->company_name
            ? $this->company_name
            : $this->full_name;
    }

    public function isCompany(): bool
    {
        return $this->account_type === 'company';
    }

    // Scopes
    public function scopeCompanies($query)   { return $query->where('account_type', 'company'); }
    public function scopeIndividuals($query) { return $query->where('account_type', 'individual'); }
    public function scopeByCity($query, string $city) { return $query->where('city', $city); }
}