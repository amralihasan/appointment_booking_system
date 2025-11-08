<?php

namespace App\Filament\Owner\Widgets;

use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();
        $trialTenants = Tenant::where('status', 'trial')->count();
        
        $totalUsers = User::whereNotNull('tenant_id')->count();
        $totalAppointments = Appointment::count();

        return [
            Stat::make(__('filament.total_tenants'), $totalTenants)
                ->description(__('filament.system_wide'))
                ->descriptionIcon('heroicon-o-building-office')
                ->color('primary'),
            
            Stat::make(__('filament.active_tenants'), $activeTenants)
                ->description(__('filament.active'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            
            Stat::make(__('filament.trial_tenants'), $trialTenants)
                ->description(__('filament.on_trial'))
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            
            Stat::make(__('filament.total_users'), $totalUsers)
                ->description(__('filament.across_all_tenants'))
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),
            
            Stat::make(__('filament.total_appointments'), $totalAppointments)
                ->description(__('filament.all_time'))
                ->descriptionIcon('heroicon-o-calendar')
                ->color('success'),
        ];
    }
}

