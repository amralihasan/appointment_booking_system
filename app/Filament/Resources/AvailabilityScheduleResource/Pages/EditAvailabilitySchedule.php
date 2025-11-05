<?php

namespace App\Filament\Resources\AvailabilityScheduleResource\Pages;

use App\Filament\Resources\AvailabilityScheduleResource;
use App\Models\AvailabilitySchedule;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAvailabilitySchedule extends EditRecord
{
    protected static string $resource = AvailabilityScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Delete All Slots for This Day')
                ->requiresConfirmation()
                ->action(function () {
                    $record = $this->record;
                    
                    // Delete all slots for this day
                    AvailabilitySchedule::where('tenant_id', $record->tenant_id)
                        ->where('user_id', $record->user_id)
                        ->where('day_of_week', $record->day_of_week)
                        ->delete();
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load all time slots for the same day, user, and tenant
        $record = $this->record;
        
        $allSlots = AvailabilitySchedule::where('tenant_id', $record->tenant_id)
            ->where('user_id', $record->user_id)
            ->where('day_of_week', $record->day_of_week)
            ->orderBy('start_time')
            ->get();

        // Transform to repeater format
        $timeSlots = $allSlots->map(function ($slot) {
            return [
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'is_active' => $slot->is_active,
            ];
        })->toArray();

        $data['time_slots'] = $timeSlots;
        $data['is_active_global'] = $allSlots->every(fn($slot) => $slot->is_active);

        // Keep the original record data for reference
        $data['original_record_id'] = $record->id;
        $data['original_day_of_week'] = $record->day_of_week;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Get time slots from form data
        $timeSlots = $data['time_slots'] ?? [];
        $isActiveGlobal = $data['is_active_global'] ?? true;
        $dayOfWeek = $data['day_of_week'] ?? $record->day_of_week;
        $tenantId = $record->tenant_id;
        $userId = $record->user_id;

        // Delete all existing slots for this day
        AvailabilitySchedule::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->delete();

        // Create new records for each time slot
        foreach ($timeSlots as $slot) {
            AvailabilitySchedule::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'day_of_week' => $dayOfWeek,
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'is_active' => $slot['is_active'] ?? $isActiveGlobal,
            ]);
        }

        // Return the first created record (or a new one if no slots)
        return AvailabilitySchedule::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->first() ?? $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
