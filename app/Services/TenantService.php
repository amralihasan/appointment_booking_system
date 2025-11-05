<?php

namespace App\Services;

use App\Models\Tenant;

class TenantService
{
    public function findBySlug(string $slug): ?Tenant
    {
        return Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->first();
    }

    public function findBySubdomain(string $subdomain): ?Tenant
    {
        return Tenant::where('subdomain', $subdomain)
            ->where('status', 'active')
            ->first();
    }
}
