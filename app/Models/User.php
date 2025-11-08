<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements HasName, FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'mobile',
        'email',
        'whatsapp',
        'password',
        'timezone',
        'language',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if user is an owner (has no tenant_id)
     */
    public function isOwner(): bool
    {
        return $this->tenant_id === null;
    }

    /**
     * Check if user can access a specific panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        $panelId = $panel->getId();

        // Owner panel: only owners can access
        if ($panelId === 'owner') {
            return $this->isOwner();
        }

        // Tenant panel: tenant users can access, owners can access when impersonating
        if ($panelId === 'admin') {
            // If user has tenant_id, they can access
            if (!$this->isOwner()) {
                return true;
            }

            // Owners can access if they're impersonating a tenant
            // This will be checked via middleware
            return session()->has('owner_impersonating_tenant_id');
        }

        return false;
    }

    /**
     * Get all tenants this user can access
     */
    public function getTenants(Panel $panel): array | Collection
    {
        // Owners can access all tenants
        if ($this->isOwner()) {
            return Tenant::all();
        }

        // Regular users can only access their own tenant
        if ($this->tenant_id) {
            return collect([$this->tenant]);
        }

        return collect([]);
    }

    /**
     * Check if user can access a specific tenant
     */
    public function canAccessTenant(Model $tenant): bool
    {
        // Owners can access all tenants
        if ($this->isOwner()) {
            return true;
        }

        // Regular users can only access their own tenant
        return $this->tenant_id === $tenant->id;
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

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFilamentName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
