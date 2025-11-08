<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'subdomain',
        'status',
        'trial_ends_at',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function availabilitySchedules(): HasMany
    {
        return $this->hasMany(AvailabilitySchedule::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function serviceQuestions(): HasManyThrough
    {
        return $this->hasManyThrough(
            ServiceQuestion::class,
            Service::class,
            'tenant_id', // Foreign key on services table
            'service_id', // Foreign key on service_questions table
            'id', // Local key on tenants table
            'id' // Local key on services table
        );
    }

    /**
     * Get category-specific field configuration
     */
    public function getCategoryFieldConfig(): ?array
    {
        return $this->category?->field_config;
    }
}
