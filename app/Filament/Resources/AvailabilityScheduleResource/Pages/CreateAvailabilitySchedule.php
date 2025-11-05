<?php

namespace App\Filament\Resources\AvailabilityScheduleResource\Pages;

use App\Filament\Resources\AvailabilityScheduleResource;
use App\Models\AvailabilitySchedule;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAvailabilitySchedule extends CreateRecord
{
    protected static string $resource = AvailabilityScheduleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Get time slots and global active status from form data
        $timeSlots = $data['time_slots'] ?? [];
        $isActiveGlobal = $data['is_active_global'] ?? true;
        $dayOfWeek = $data['day_of_week'];
        $tenantId = auth()->user()->tenant_id;
        $userId = auth()->id();

        // Create multiple records - one for each time slot
        $createdRecords = [];
        
        foreach ($timeSlots as $slot) {
            $createdRecords[] = AvailabilitySchedule::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'day_of_week' => $dayOfWeek,
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'is_active' => $slot['is_active'] ?? $isActiveGlobal,
            ]);
        }

        // Return the first created record (for redirect purposes)
        return $createdRecords[0] ?? new AvailabilitySchedule();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
