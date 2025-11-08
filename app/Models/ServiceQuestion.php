<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class ServiceQuestion extends Model
{
    protected $fillable = [
        'service_id',
        'question_text',
        'field_type',
        'options',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ServiceQuestionAnswer::class);
    }

    public function tenant(): HasOneThrough
    {
        return $this->hasOneThrough(
            Tenant::class,
            Service::class,
            'tenant_id', // Foreign key on services table (services.tenant_id)
            'id', // Primary key on tenants table (tenants.id)
            'service_id', // Local key on service_questions table
            'id' // Local key on services table (services.id)
        );
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }
}
