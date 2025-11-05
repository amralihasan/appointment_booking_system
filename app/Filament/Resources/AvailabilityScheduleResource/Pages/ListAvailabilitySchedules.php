<?php

namespace App\Filament\Resources\AvailabilityScheduleResource\Pages;

use App\Filament\Resources\AvailabilityScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAvailabilitySchedules extends ListRecords
{
    protected static string $resource = AvailabilityScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
