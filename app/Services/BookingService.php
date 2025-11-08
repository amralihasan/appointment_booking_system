<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Service;
use App\Models\ServiceQuestionAnswer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    protected AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Create a new appointment booking
     */
    public function createBooking(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $service = Service::findOrFail($data['service_id']);
            $employeeId = $data['employee_id'] ?? null;
            
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
            
            // If date_time is already a Carbon instance in UTC, use it directly
            // Otherwise, parse it assuming it's in UTC (from the booking flow)
            if ($data['date_time'] instanceof Carbon) {
                $dateTime = $data['date_time']->copy();
            } else {
                $dateTime = Carbon::parse($data['date_time'], 'UTC');
            }

            // Validate availability (convert to coach's timezone for comparison)
            $dateTimeInCoachTz = $dateTime->copy()->setTimezone($coachTimezone);
            if (!$this->validateAvailability($service, $dateTimeInCoachTz, $data['tenant_id'], $employeeId)) {
                throw new \Exception('This time slot is no longer available.');
            }

            // Find or create contact
            $contact = $this->findOrCreateContact($data);

            // Ensure date_time is properly converted to UTC for storage
            // The dateTime is already in UTC from the booking flow, but let's make sure
            $dateTimeForStorage = $dateTime->copy();
            if (!$dateTimeForStorage->timezone || $dateTimeForStorage->timezone->getName() !== 'UTC') {
                $dateTimeForStorage = $dateTimeForStorage->utc();
            }
            
            // Create appointment - pass as Carbon instance, model will handle conversion
            $appointment = Appointment::create([
                'tenant_id' => $data['tenant_id'],
                'user_id' => $service->user_id,
                'service_id' => $service->id,
                'employee_id' => $employeeId,
                'contact_id' => $contact?->id,
                'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'],
                'client_email' => $data['client_email'] ?? null,
                'date_time' => $dateTimeForStorage,
                'duration' => $service->duration,
                'status' => 'booked',
                'notes' => $data['notes'] ?? null,
            ]);

            // Store question answers if provided
            if (isset($data['question_answers']) && is_array($data['question_answers'])) {
                foreach ($data['question_answers'] as $questionId => $answerValue) {
                    // Skip empty answers (for optional questions)
                    if (empty($answerValue) || (is_array($answerValue) && empty($answerValue))) {
                        continue;
                    }

                    // For select_multiple, store as JSON
                    $answerToStore = is_array($answerValue) ? json_encode($answerValue) : $answerValue;

                    ServiceQuestionAnswer::create([
                        'appointment_id' => $appointment->id,
                        'service_question_id' => $questionId,
                        'answer_value' => $answerToStore,
                    ]);
                }
            }

            return $appointment;
        });
    }

    /**
     * Validate if a time slot is still available
     */
    protected function validateAvailability(Service $service, Carbon $dateTime, int $tenantId, ?int $employeeId = null): bool
    {
        // Calculate buffer time zones
        $timeBefore = $service->time_before ?? 0;
        $timeAfter = $service->time_after ?? 0;
        
        // Calculate the actual time range to check (including buffer zones)
        $checkStart = $dateTime->copy()->subMinutes($timeBefore);
        $checkEnd = $dateTime->copy()->addMinutes($service->duration)->addMinutes($timeAfter);
        
        // Convert to UTC for database comparison (database stores in UTC)
        $checkStartUtc = $checkStart->copy()->utc();
        $checkEndUtc = $checkEnd->copy()->utc();
        
        // Also calculate the exact slot times for group service counting
        $slotStartUtc = $dateTime->copy()->utc();
        $slotEndUtc = $dateTime->copy()->addMinutes($service->duration)->utc();

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

        // For one-to-one: check if any appointment exists that conflicts
        if ($service->type === 'one') {
            return $conflictingAppointments->isEmpty();
        }

        // For group: check if spots are available
        if ($service->type === 'group' && $service->max_spots) {
            // If there are conflicting appointments (buffer zone conflicts), slot is not available
            if (!$conflictingAppointments->isEmpty()) {
                return false;
            }
            
            // Count appointments that overlap with our exact slot (not buffer zone)
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

            return $bookedCount < $service->max_spots;
        }

        return false;
    }

    /**
     * Find or create a contact
     */
    protected function findOrCreateContact(array $data): ?Contact
    {
        if (!isset($data['client_phone']) || !isset($data['user_id'])) {
            return null;
        }

        $contact = Contact::where('tenant_id', $data['tenant_id'])
            ->where('user_id', $data['user_id'])
            ->where('mobile', $data['client_phone'])
            ->first();

        if (!$contact && isset($data['client_name'])) {
            $nameParts = explode(' ', trim($data['client_name']), 2);
            $contact = Contact::create([
                'tenant_id' => $data['tenant_id'],
                'user_id' => $data['user_id'],
                'first_name' => $nameParts[0] ?? '',
                'last_name' => $nameParts[1] ?? '',
                'mobile' => $data['client_phone'],
                'email' => $data['client_email'] ?? null,
            ]);
        }

        return $contact;
    }
}
