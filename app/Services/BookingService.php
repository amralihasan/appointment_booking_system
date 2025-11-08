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
            $user = $service->user;
            $coachTimezone = $user->timezone ?? 'Africa/Cairo';
            
            // If date_time is already a Carbon instance in UTC, use it directly
            // Otherwise, parse it assuming it's in UTC (from the booking flow)
            if ($data['date_time'] instanceof Carbon) {
                $dateTime = $data['date_time']->copy();
            } else {
                $dateTime = Carbon::parse($data['date_time'], 'UTC');
            }

            // Validate availability (convert to coach's timezone for comparison)
            $dateTimeInCoachTz = $dateTime->copy()->setTimezone($coachTimezone);
            if (!$this->validateAvailability($service, $dateTimeInCoachTz, $data['tenant_id'])) {
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
    protected function validateAvailability(Service $service, Carbon $dateTime, int $tenantId): bool
    {
        // Convert to UTC for database comparison (database stores in UTC)
        $slotStart = $dateTime->copy()->utc();
        $slotEnd = $dateTime->copy()->addMinutes($service->duration)->utc();

        // For one-to-one: check if slot is already booked
        if ($service->type === 'one') {
            $exists = Appointment::where('tenant_id', $tenantId)
                ->where('service_id', $service->id)
                ->where('status', 'booked')
                ->whereBetween('date_time', [$slotStart, $slotEnd->copy()->subSecond()])
                ->exists();

            return !$exists;
        }

        // For group: check if spots are available
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
