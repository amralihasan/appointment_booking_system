<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'description',
        'price',
        'duration',
        'time_before',
        'time_after',
        'booking_scope_days',
        'type',
        'max_spots',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'time_before' => 'integer',
            'time_after' => 'integer',
            'booking_scope_days' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ServiceTranslation::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ServiceQuestion::class)->ordered();
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_service')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Get the translated name for the current locale
     */
    public function getTranslatedNameAttribute(): string
    {
        $locale = app()->getLocale();
        $translation = $this->translations()->where('locale', $locale)->first();
        
        return $translation ? $translation->name : $this->name;
    }

    /**
     * Get the translated description for the current locale
     */
    public function getTranslatedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        $translation = $this->translations()->where('locale', $locale)->first();
        
        return $translation ? $translation->description : $this->description;
    }
}
