<?php

namespace App\Livewire\Booking;

use App\Models\Service;
use App\Models\Tenant;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Livewire\Component;
use Carbon\Carbon;

class Show extends Component
{
    public ?Tenant $tenant = null;
    public ?Service $service = null;
    public string $tenantSlug;
    public string $serviceSlug;

    // Step 1: Date/Time Selection
    public int $currentStep = 1;
    public ?string $selectedDate = null;
    public ?string $selectedTime = null;
    public array $availableDates = [];
    public array $availableTimeSlots = [];

    // Step 2: Client Information
    public string $clientName = '';
    public string $clientFirstName = '';
    public string $clientLastName = '';
    public string $clientPhone = '';
    public ?string $clientEmail = null;
    public ?string $notes = null;

    // Step 3: Confirmation
    public ?\App\Models\Appointment $appointment = null;

    protected AvailabilityService $availabilityService;
    protected BookingService $bookingService;

    public function boot(AvailabilityService $availabilityService, BookingService $bookingService)
    {
        $this->availabilityService = $availabilityService;
        $this->bookingService = $bookingService;
    }

    public function mount(string $tenantSlug, string $serviceSlug)
    {
        $this->tenantSlug = $tenantSlug;
        $this->serviceSlug = $serviceSlug;

        // Load tenant and service
        $this->tenant = \App\Models\Tenant::where('slug', $tenantSlug)
            ->where('status', 'active')
            ->firstOrFail();

        $this->service = Service::where('tenant_id', $this->tenant->id)
            ->where('slug', $serviceSlug)
            ->where('is_active', true)
            ->firstOrFail();

        // Generate available dates (next 30 days)
        $this->generateAvailableDates();
    }

    public function selectDate(string $date)
    {
        $this->selectedDate = $date;
        $this->selectedTime = null;
        $this->loadTimeSlots($date);
    }

    public function selectTime(string $time)
    {
        $this->selectedTime = $time;
        $this->currentStep = 2;
    }

    public function goBackToStep1()
    {
        $this->currentStep = 1;
    }

    public function submitBooking()
    {
        $this->validate([
            'clientFirstName' => 'required|string|max:255',
            'clientLastName' => 'required|string|max:255',
            'clientPhone' => 'required|string|max:255',
            'clientEmail' => 'nullable|email|max:255',
        ]);

        try {
            $dateTime = Carbon::parse($this->selectedDate . ' ' . $this->selectedTime);

            $this->appointment = $this->bookingService->createBooking([
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->service->user_id,
                'service_id' => $this->service->id,
                'client_name' => trim($this->clientFirstName . ' ' . $this->clientLastName),
                'client_phone' => $this->clientPhone,
                'client_email' => $this->clientEmail,
                'date_time' => $dateTime,
                'notes' => $this->notes,
            ]);

            $this->currentStep = 3;
        } catch (\Exception $e) {
            $this->addError('booking', $e->getMessage());
        }
    }

    protected function generateAvailableDates()
    {
        $dates = [];
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(30);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek;

            // Check if there's availability for this day
            $hasAvailability = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                ->where('user_id', $this->service->user_id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->exists();

            if ($hasAvailability) {
                $dates[] = [
                    'date' => $date->format('Y-m-d'),
                    'display' => $date->format('M d, Y'),
                    'day' => $date->format('D'),
                ];
            }
        }

        $this->availableDates = $dates;
    }

    protected function loadTimeSlots(string $date)
    {
        $this->availableTimeSlots = $this->availabilityService->getAvailableTimeSlots(
            $this->tenant->id,
            $this->service->id,
            $date
        );
    }

    public function getRemainingSpotsProperty(): ?int
    {
        if (!$this->selectedDate || !$this->selectedTime || $this->service->type !== 'group') {
            return null;
        }

        $dateTime = Carbon::parse($this->selectedDate . ' ' . $this->selectedTime);
        return $this->availabilityService->getRemainingSpots(
            $this->tenant->id,
            $this->service->id,
            $dateTime
        );
    }

    public function render()
    {
        return view('livewire.booking.show');
    }
}
