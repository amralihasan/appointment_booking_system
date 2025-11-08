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
    public function getAvailableTimeSlots(int $tenantId, int $serviceId, string $date, ?int $employeeId = null): array
    {
        $service = Service::find($serviceId);
        if (!$service || $service->tenant_id !== $tenantId) {
            return [];
        }

        // Determine timezone: use employee's timezone if provided, otherwise use service user's timezone
        $coachTimezone = 'Africa/Cairo';
        if ($employeeId) {
            $employee = \App\Models\Employee::find($employeeId);
            if ($employee) {
                $coachTimezone = $employee->timezone ?? 'Africa/Cairo';
            }
        } else {
            $user = $service->user;
            $coachTimezone = $user->timezone ?? 'Africa/Cairo';
        }

        // Parse date in coach's timezone
        $dateCarbon = Carbon::parse($date, $coachTimezone);
        $dayOfWeek = $dateCarbon->dayOfWeek; // 0-6

        // Get availability schedules for this day of week
        if ($employeeId) {
            // First check for employee-specific schedules
            $schedulesQuery = AvailabilitySchedule::where('tenant_id', $tenantId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->where('employee_id', $employeeId);
            
            $schedules = $schedulesQuery->get();
            
            // If no employee schedules, fall back to user schedules (employee inherits from service owner)
            if ($schedules->isEmpty()) {
                $schedulesQuery = AvailabilitySchedule::where('tenant_id', $tenantId)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->where('user_id', $service->user_id)
                    ->whereNull('employee_id');
                
                $schedules = $schedulesQuery->get();
            }
        } else {
            // Filter by user if no employee
            $schedulesQuery = AvailabilitySchedule::where('tenant_id', $tenantId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->where('user_id', $service->user_id)
                ->whereNull('employee_id');
            
            $schedules = $schedulesQuery->get();
        }

        if ($schedules->isEmpty()) {
            return [];
        }

        $availableSlots = [];

        foreach ($schedules as $schedule) {
            // Parse time strings properly in coach's timezone
            $startTimeStr = is_string($schedule->start_time) ? $schedule->start_time : $schedule->start_time->format('H:i:s');
            $endTimeStr = is_string($schedule->end_time) ? $schedule->end_time : $schedule->end_time->format('H:i:s');
            
            $startTime = Carbon::parse($date . ' ' . $startTimeStr, $coachTimezone);
            $endTime = Carbon::parse($date . ' ' . $endTimeStr, $coachTimezone);
            $currentTime = $startTime->copy();

            // Get buffer times
            $timeBefore = $service->time_before ?? 0;
            $timeAfter = $service->time_after ?? 0;
            
            // Calculate slot increment: duration + buffer before + buffer after
            // This ensures buffer zones don't overlap between consecutive slots
            // Example: 60 min duration + 5 min before + 10 min after = 75 min spacing
            // Slot 1: 7:15 AM - 8:15 AM (blocks 7:10 AM - 8:25 AM)
            // Slot 2: 8:30 AM - 9:30 AM (blocks 8:25 AM - 9:40 AM)
            $slotIncrement = $service->duration + $timeBefore + $timeAfter;
            
            // Start from the schedule start time
            // The first slot always starts at the schedule start time
            $currentTime = $startTime->copy();
            $isFirstSlot = true;
            
            while ($currentTime->copy()->addMinutes($service->duration)->lte($endTime)) {
                $slotStart = $currentTime->copy();
                $slotEnd = $currentTime->copy()->addMinutes($service->duration);
                
                // Check if slot would extend beyond schedule end when accounting for buffer after
                if ($slotEnd->copy()->addMinutes($timeAfter)->gt($endTime)) {
                    // This slot would extend beyond the schedule end with buffer, skip it
                    break;
                }
                
                // For the first slot, always allow it even if buffer before extends before schedule start
                // For subsequent slots, check if buffer before would extend before schedule start
                if (!$isFirstSlot && $timeBefore > 0 && $slotStart->copy()->subMinutes($timeBefore)->lt($startTime)) {
                    // This slot would start before the schedule start with buffer, skip it
                    $currentTime->addMinutes($slotIncrement);
                    continue;
                }

                // Check if this slot is available (convert to UTC for database comparison)
                if ($this->isSlotAvailable($service, $slotStart, $slotEnd, $tenantId, $employeeId)) {
                    $availableSlots[] = [
                        'start' => $slotStart->format('H:i'),
                        'end' => $slotEnd->format('H:i'),
                        'display' => $slotStart->format('g:i A') . ' - ' . $slotEnd->format('g:i A'),
                    ];
                }

                // Mark that we've processed the first slot
                $isFirstSlot = false;

                // Move to next slot: current slot end + buffer after + buffer before for next slot
                // This ensures no overlap between buffer zones
                $currentTime->addMinutes($slotIncrement);
            }
        }

        return $availableSlots;
    }

    /**
     * Check if a time slot is available
     */
    protected function isSlotAvailable(Service $service, Carbon $slotStart, Carbon $slotEnd, int $tenantId, ?int $employeeId = null): bool
    {
        // Calculate buffer time zones
        $timeBefore = $service->time_before ?? 0;
        $timeAfter = $service->time_after ?? 0;
        
        // Calculate the actual time range to check (including buffer zones)
        // We need to check if any existing appointment overlaps with our buffer zone
        $checkStart = $slotStart->copy()->subMinutes($timeBefore);
        $checkEnd = $slotEnd->copy()->addMinutes($timeAfter);
        
        // Convert to UTC for database comparison (database stores in UTC)
        $checkStartUtc = $checkStart->copy()->utc();
        $checkEndUtc = $checkEnd->copy()->utc();

        // Get all appointments that might conflict (overlap with our buffer zone)
        // We need to check appointments that start before our check end
        // and calculate their end time in PHP to check for overlap
        $potentialConflictsQuery = Appointment::where('tenant_id', $tenantId)
            ->where('service_id', $service->id)
            ->where('status', 'booked')
            ->where('date_time', '<', $checkEndUtc);

        // Filter by employee if provided
        if ($employeeId) {
            $potentialConflictsQuery->where('employee_id', $employeeId);
        } else {
            // If no employee, check appointments without employee or with same user
            $potentialConflictsQuery->where(function ($query) use ($service) {
                $query->whereNull('employee_id')
                    ->orWhere('user_id', $service->user_id);
            });
        }

        $potentialConflicts = $potentialConflictsQuery->get();
        
        // Filter in PHP to check actual overlaps (accounting for duration)
        $conflictingAppointments = $potentialConflicts->filter(function ($appointment) use ($checkStartUtc, $checkEndUtc) {
            $appointmentStart = Carbon::parse($appointment->date_time);
            $appointmentEnd = $appointmentStart->copy()->addMinutes($appointment->duration);
            
            // Check if appointment overlaps with our buffer zone
            // An appointment overlaps if:
            // - Its start time is before our check end AND
            // - Its end time is after our check start
            return $appointmentStart->lt($checkEndUtc) && $appointmentEnd->gt($checkStartUtc);
        });

        // For one-to-one services: check if any appointment exists that conflicts
        if ($service->type === 'one') {
            return $conflictingAppointments->isEmpty();
        }

        // For group services: check if spots are available
        if ($service->type === 'group' && $service->max_spots) {
            // Count appointments that overlap with our exact slot (not buffer zone)
            $slotStartUtc = $slotStart->copy()->utc();
            $slotEndUtc = $slotEnd->copy()->utc();
            
            // Get potential conflicts and filter in PHP
            $potentialSlotConflictsQuery = Appointment::where('tenant_id', $tenantId)
                ->where('service_id', $service->id)
                ->where('status', 'booked')
                ->where('date_time', '<', $slotEndUtc);

            // Filter by employee if provided
            if ($employeeId) {
                $potentialSlotConflictsQuery->where('employee_id', $employeeId);
            } else {
                // If no employee, check appointments without employee or with same user
                $potentialSlotConflictsQuery->where(function ($query) use ($service) {
                    $query->whereNull('employee_id')
                        ->orWhere('user_id', $service->user_id);
                });
            }

            $potentialSlotConflicts = $potentialSlotConflictsQuery->get();
            
            $bookedCount = $potentialSlotConflicts->filter(function ($appointment) use ($slotStartUtc, $slotEndUtc) {
                $appointmentStart = Carbon::parse($appointment->date_time);
                $appointmentEnd = $appointmentStart->copy()->addMinutes($appointment->duration);
                
                // Check if appointment overlaps with our exact slot
                return $appointmentStart->lt($slotEndUtc) && $appointmentEnd->gt($slotStartUtc);
            })->count();

            // If there are conflicting appointments (buffer zone conflicts), slot is not available
            if (!$conflictingAppointments->isEmpty()) {
                return false;
            }

            return $bookedCount < $service->max_spots;
        }

        return false;
    }

    /**
     * Get remaining spots for a group service at a specific time
     */
    public function getRemainingSpots(int $tenantId, int $serviceId, Carbon $dateTime, ?int $employeeId = null): int
    {
        $service = Service::find($serviceId);
        if (!$service || $service->type !== 'group' || !$service->max_spots) {
            return 0;
        }

        // Convert to UTC for database comparison (database stores in UTC)
        $slotStartUtc = $dateTime->copy()->utc();
        $slotEndUtc = $dateTime->copy()->addMinutes($service->duration)->utc();

        // Get potential conflicts and filter in PHP
        $potentialConflictsQuery = Appointment::where('tenant_id', $tenantId)
            ->where('service_id', $serviceId)
            ->where('status', 'booked')
            ->where('date_time', '<', $slotEndUtc);

        // Filter by employee if provided
        if ($employeeId) {
            $potentialConflictsQuery->where('employee_id', $employeeId);
        } else {
            // If no employee, check appointments without employee or with same user
            $potentialConflictsQuery->where(function ($query) use ($service) {
                $query->whereNull('employee_id')
                    ->orWhere('user_id', $service->user_id);
            });
        }

        $potentialConflicts = $potentialConflictsQuery->get();
        
        $bookedCount = $potentialConflicts->filter(function ($appointment) use ($slotStartUtc, $slotEndUtc) {
            $appointmentStart = Carbon::parse($appointment->date_time);
            $appointmentEnd = $appointmentStart->copy()->addMinutes($appointment->duration);
            
            // Check if appointment overlaps with our exact slot
            return $appointmentStart->lt($slotEndUtc) && $appointmentEnd->gt($slotStartUtc);
        })->count();

        return max(0, $service->max_spots - $bookedCount);
    }
}
