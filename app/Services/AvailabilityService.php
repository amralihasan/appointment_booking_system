<?php

namespace App\Services;

use App\Models\AvailabilitySchedule;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Get available time slots for a specific date and service
     */
    public function getAvailableTimeSlots(int $tenantId, int $serviceId, string $date): array
    {
        $service = Service::find($serviceId);
        if (!$service || $service->tenant_id !== $tenantId) {
            return [];
        }

        $dateCarbon = Carbon::parse($date);
        $dayOfWeek = $dateCarbon->dayOfWeek; // 0-6

        // Get availability schedules for this day of week
        $schedules = AvailabilitySchedule::where('tenant_id', $tenantId)
            ->where('user_id', $service->user_id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $availableSlots = [];

        foreach ($schedules as $schedule) {
            // Parse time strings properly
            $startTimeStr = is_string($schedule->start_time) ? $schedule->start_time : $schedule->start_time->format('H:i:s');
            $endTimeStr = is_string($schedule->end_time) ? $schedule->end_time : $schedule->end_time->format('H:i:s');
            
            $startTime = Carbon::parse($date . ' ' . $startTimeStr);
            $endTime = Carbon::parse($date . ' ' . $endTimeStr);
            $currentTime = $startTime->copy();

            while ($currentTime->copy()->addMinutes($service->duration)->lte($endTime)) {
                $slotStart = $currentTime->copy();
                $slotEnd = $currentTime->copy()->addMinutes($service->duration);

                // Check if this slot is available
                if ($this->isSlotAvailable($service, $slotStart, $slotEnd, $tenantId)) {
                    $availableSlots[] = [
                        'start' => $slotStart->format('H:i'),
                        'end' => $slotEnd->format('H:i'),
                        'display' => $slotStart->format('g:i A') . ' - ' . $slotEnd->format('g:i A'),
                    ];
                }

                $currentTime->addMinutes($service->duration);
            }
        }

        return $availableSlots;
    }

    /**
     * Check if a time slot is available
     */
    protected function isSlotAvailable(Service $service, Carbon $slotStart, Carbon $slotEnd, int $tenantId): bool
    {
        // For one-to-one services: check if any appointment exists for this time
        if ($service->type === 'one') {
            $exists = Appointment::where('tenant_id', $tenantId)
                ->where('service_id', $service->id)
                ->where('status', 'booked')
                ->whereBetween('date_time', [$slotStart, $slotEnd->copy()->subSecond()])
                ->exists();

            return !$exists;
        }

        // For group services: check if spots are available
        if ($service->type === 'group' && $service->max_spots) {
            $bookedCount = Appointment::where('tenant_id', $tenantId)
                ->where('service_id', $service->id)
                ->where('status', 'booked')
                ->whereBetween('date_time', [$slotStart, $slotEnd->copy()->subSecond()])
                ->count();

            return $bookedCount < $service->max_spots;
        }

        return false;
    }

    /**
     * Get remaining spots for a group service at a specific time
     */
    public function getRemainingSpots(int $tenantId, int $serviceId, Carbon $dateTime): int
    {
        $service = Service::find($serviceId);
        if (!$service || $service->type !== 'group' || !$service->max_spots) {
            return 0;
        }

        $slotStart = $dateTime->copy();
        $slotEnd = $dateTime->copy()->addMinutes($service->duration);

        $bookedCount = Appointment::where('tenant_id', $tenantId)
            ->where('service_id', $serviceId)
            ->where('status', 'booked')
            ->whereBetween('date_time', [$slotStart, $slotEnd->copy()->subSecond()])
            ->count();

        return max(0, $service->max_spots - $bookedCount);
    }
}
