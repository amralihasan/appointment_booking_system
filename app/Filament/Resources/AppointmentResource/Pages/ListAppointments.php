<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Default: show only incoming appointments (date_time >= now)
        // Check if the show_past filter is active - if not, filter to incoming only
        $filters = $this->tableFilters ?? [];
        $showPastActive = isset($filters['show_past']['isActive']) && $filters['show_past']['isActive'] === true;
        
        if (!$showPastActive) {
            $userTimezone = auth()->user()->timezone ?? 'Africa/Cairo';
            $now = Carbon::now($userTimezone)->utc(); // Convert to UTC for database comparison
            return $query->where('date_time', '>=', $now);
        }
        
        return $query;
    }

    protected static string $view = 'filament.resources.appointment-resource.pages.list-appointments';
}
