<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'service_id',
        'employee_id',
        'contact_id',
        'client_name',
        'client_phone',
        'client_email',
        'date_time',
        'duration',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_time' => 'datetime',
        ];
    }
    
    /**
     * Override to ensure date_time is always stored as UTC string
     */
    public function setAttribute($key, $value)
    {
        if ($key === 'date_time' && $value !== null) {
            // If it's a Carbon instance, ensure it's in UTC and convert to string
            if ($value instanceof \Carbon\Carbon) {
                $value = $value->utc()->format('Y-m-d H:i:s');
            }
        }
        
        return parent::setAttribute($key, $value);
    }
    
    /**
     * Override to ensure date_time is always parsed as UTC from database
     */
    protected function castAttribute($key, $value)
    {
        if ($key === 'date_time' && $value !== null) {
            // Always parse as UTC from database (database stores UTC)
            if (is_string($value)) {
                return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $value, 'UTC');
            }
        }
        
        return parent::castAttribute($key, $value);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeBooked($query)
    {
        return $query->where('status', 'booked');
    }

    public function questionAnswers(): HasMany
    {
        return $this->hasMany(ServiceQuestionAnswer::class);
    }
}
