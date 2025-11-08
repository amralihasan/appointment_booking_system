<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OwnerImpersonation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Only apply to owners accessing tenant panel
        if ($user && $user->isOwner()) {
            // Check if owner is impersonating a tenant via session
            if (session()->has('owner_impersonating_tenant_id')) {
                $tenantId = session('owner_impersonating_tenant_id');
                $tenant = \App\Models\Tenant::find($tenantId);
                
                if ($tenant && $user->canAccessTenant($tenant)) {
                    // Set the tenant for Filament
                    Filament::setTenant($tenant);
                }
            }
        }

        return $next($request);
    }
}

