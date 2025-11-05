<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class AppointmentStats extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $userTimezone = $user->timezone ?? 'Africa/Cairo';
        $now = Carbon::now($userTimezone);
        $todayStart = $now->copy()->startOfDay()->utc();
        $todayEnd = $now->copy()->endOfDay()->utc();

        $totalAppointments = Appointment::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->count();

        $upcomingAppointments = Appointment::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->where('status', 'booked')
            ->where('date_time', '>=', $now->utc())
            ->count();

        $todayAppointments = Appointment::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->where('status', 'booked')
            ->whereBetween('date_time', [$todayStart, $todayEnd])
            ->count();

        $totalServices = Service::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->count();

        $totalContacts = Contact::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->count();

        return [
            Stat::make('Total Appointments', $totalAppointments)
                ->description('All time')
                ->descriptionIcon('heroicon-o-calendar'),
            
            Stat::make('Upcoming Appointments', $upcomingAppointments)
                ->description('Scheduled')
                ->descriptionIcon('heroicon-o-clock')
                ->color('success'),
            
            Stat::make("Today's Appointments", $todayAppointments)
                ->description('Today')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('warning'),
            
            Stat::make('Active Services', $totalServices)
                ->description('Services')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('info'),
        ];
    }
}

