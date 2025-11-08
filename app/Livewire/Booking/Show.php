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
    public ?\App\Models\Employee $employee = null;
    public string $tenantSlug;
    public string $serviceSlug;
    public ?string $employeeSlug = null;

    // Step 1: Date/Time Selection
    public int $currentStep = 1;
    public ?string $selectedDate = null;
    public ?string $selectedTime = null;
    public array $availableDates = [];
    public array $availableTimeSlots = [];
    
    // Calendar properties
    public $currentMonth;
    public $currentYear;
    public $calendarDays = [];

    // Step 2: Client Information
    public string $clientName = '';
    public string $clientFirstName = '';
    public string $clientLastName = '';
    public string $clientPhone = '';
    public ?string $clientEmail = null;
    public ?string $notes = null;
    public array $questionAnswers = [];

    // Step 3: Confirmation
    public ?\App\Models\Appointment $appointment = null;

    protected AvailabilityService $availabilityService;
    protected BookingService $bookingService;

    public function boot(AvailabilityService $availabilityService, BookingService $bookingService)
    {
        $this->availabilityService = $availabilityService;
        $this->bookingService = $bookingService;
    }

    /**
     * Get the coach's timezone
     */
    protected function getCoachTimezone(): string
    {
        // Use employee's timezone if provided, otherwise use service user's timezone
        if ($this->employee) {
            return $this->employee->timezone ?? 'Africa/Cairo';
        }
        return $this->service?->user?->timezone ?? 'Africa/Cairo';
    }

    public function mount(string $tenantSlug, string $serviceSlug, ?string $employeeSlug = null)
    {
        $this->tenantSlug = $tenantSlug;
        $this->serviceSlug = $serviceSlug;
        $this->employeeSlug = $employeeSlug;

        // Load tenant and service
        // Allow both 'active' and 'trial' status tenants
        $this->tenant = \App\Models\Tenant::where('slug', $tenantSlug)
            ->whereIn('status', ['active', 'trial'])
            ->firstOrFail();

        $this->service = Service::where('tenant_id', $this->tenant->id)
            ->where('slug', $serviceSlug)
            ->where('is_active', true)
            ->with(['questions', 'user'])
            ->firstOrFail();

        // Load employee if provided
        if ($employeeSlug) {
            $this->employee = \App\Models\Employee::where('tenant_id', $this->tenant->id)
                ->where('slug', $employeeSlug)
                ->where('is_active', true)
                ->firstOrFail();
            
            // Verify employee has access to this service
            if (!$this->service->employees()->where('employees.id', $this->employee->id)->exists()) {
                abort(404, 'Employee does not have access to this service.');
            }
        }
        
        // Initialize question answers
        if ($this->service->questions) {
            foreach ($this->service->questions as $question) {
                if ($question->field_type === 'select_multiple') {
                    $this->questionAnswers[$question->id] = [];
                } else {
                    $this->questionAnswers[$question->id] = '';
                }
            }
        }

        // Initialize calendar to current month
        $this->currentMonth = Carbon::now()->month;
        $this->currentYear = Carbon::now()->year;
        
        // Initialize selectedDate to null to ensure fresh selection
        $this->selectedDate = null;
        
        // Automatically select the first available date FIRST (this will set selectedDate and update calendar)
        // This will also call generateCalendar() internally, so we don't need to call it again
        $this->selectFirstAvailableDate();
        
        // Ensure calendar is regenerated one more time to reflect the selected date
        if ($this->selectedDate) {
            $this->generateCalendar();
        }
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $date->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->generateCalendar();
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $date->subMonth();
        
        // Don't allow navigating to past months
        $today = Carbon::today();
        $firstDayOfPreviousMonth = $date->copy()->startOfMonth();
        
        if ($firstDayOfPreviousMonth->lt($today)) {
            // If the previous month is in the past, go to current month instead
            $this->currentMonth = $today->month;
            $this->currentYear = $today->year;
        } else {
            $this->currentMonth = $date->month;
            $this->currentYear = $date->year;
        }
        
        $this->generateCalendar();
    }

    public function selectDate(string $date)
    {
        // Prevent selecting past dates or dates beyond booking scope
        $selectedDate = Carbon::parse($date);
        $today = Carbon::today();
        $bookingScopeDays = $this->service->booking_scope_days ?? 30;
        $maxBookingDate = $today->copy()->addDays($bookingScopeDays);
        
        if ($selectedDate->lt($today)) {
            $this->addError('selectedDate', 'Cannot select past dates. Please select a future date.');
            return;
        }
        
        if ($selectedDate->gt($maxBookingDate)) {
            $this->addError('selectedDate', 'Cannot select dates beyond the booking scope. Please select a date within ' . $bookingScopeDays . ' days.');
            return;
        }
        
        $this->selectedDate = $date;
        $this->selectedTime = null;
        
        // Update calendar month if needed to show the selected date
        if ($selectedDate->month != $this->currentMonth || $selectedDate->year != $this->currentYear) {
            $this->currentMonth = $selectedDate->month;
            $this->currentYear = $selectedDate->year;
            $this->generateCalendar();
        } else {
            // Regenerate calendar to update selected state
            $this->generateCalendar();
        }
        
        $this->loadTimeSlots($date);
    }

    public function selectTime(string $time)
    {
        // Prevent selecting past times
        if (!$this->selectedDate) {
            return;
        }
        
        $coachTimezone = $this->getCoachTimezone();
        $dateTime = Carbon::parse($this->selectedDate . ' ' . $time, $coachTimezone);
        $now = Carbon::now($coachTimezone);
        
        if ($dateTime->lt($now)) {
            $this->addError('selectedTime', 'Cannot select past time slots. Please select a future time.');
            return;
        }
        
        $this->selectedTime = $time;
        $this->currentStep = 2;
    }

    public function goBackToStep1()
    {
        $this->currentStep = 1;
    }

    public function submitBooking()
    {
        $rules = [
            'clientFirstName' => 'required|string|max:255',
            'clientLastName' => 'required|string|max:255',
            'clientPhone' => 'required|string|max:255',
            'clientEmail' => 'nullable|email|max:255',
        ];

        // Add validation rules for required questions
        if ($this->service && $this->service->questions) {
            foreach ($this->service->questions as $question) {
                if ($question->is_required) {
                    if ($question->field_type === 'select_multiple') {
                        $rules['questionAnswers.' . $question->id] = 'required|array|min:1';
                    } elseif ($question->field_type === 'email') {
                        $rules['questionAnswers.' . $question->id] = 'required|email|max:255';
                    } elseif ($question->field_type === 'number') {
                        $rules['questionAnswers.' . $question->id] = 'required|numeric';
                    } elseif ($question->field_type === 'date') {
                        $rules['questionAnswers.' . $question->id] = 'required|date';
                    } else {
                        $rules['questionAnswers.' . $question->id] = 'required|string|max:1000';
                    }
                } else {
                    if ($question->field_type === 'select_multiple') {
                        $rules['questionAnswers.' . $question->id] = 'nullable|array';
                    } elseif ($question->field_type === 'email') {
                        $rules['questionAnswers.' . $question->id] = 'nullable|email|max:255';
                    } elseif ($question->field_type === 'number') {
                        $rules['questionAnswers.' . $question->id] = 'nullable|numeric';
                    } elseif ($question->field_type === 'date') {
                        $rules['questionAnswers.' . $question->id] = 'nullable|date';
                    } else {
                        $rules['questionAnswers.' . $question->id] = 'nullable|string|max:1000';
                    }
                }
            }
        }

        $this->validate($rules);

        try {
            // Final validation: prevent booking in the past
            if (!$this->selectedDate || !$this->selectedTime) {
                throw new \Exception('Please select a date and time for your appointment.');
            }
            
            // Parse date/time in the coach's timezone
            $coachTimezone = $this->getCoachTimezone();
            $dateTime = Carbon::parse($this->selectedDate . ' ' . $this->selectedTime, $coachTimezone);
            $now = Carbon::now($coachTimezone);
            
            if ($dateTime->lte($now)) {
                throw new \Exception('Cannot book appointments in the past. Please select a future date and time.');
            }
            
            // Convert to UTC for storage (Laravel will handle this automatically with the datetime cast)
            $dateTime = $dateTime->utc();

            $this->appointment = $this->bookingService->createBooking([
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->service->user_id,
                'service_id' => $this->service->id,
                'employee_id' => $this->employee?->id,
                'client_name' => trim($this->clientFirstName . ' ' . $this->clientLastName),
                'client_phone' => $this->clientPhone,
                'client_email' => $this->clientEmail,
                'date_time' => $dateTime,
                'notes' => $this->notes,
                'question_answers' => $this->questionAnswers,
            ]);

            $this->currentStep = 3;
        } catch (\Exception $e) {
            $this->addError('booking', $e->getMessage());
        }
    }

    protected function generateCalendar()
    {
        $firstDayOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $lastDayOfMonth = $firstDayOfMonth->copy()->endOfMonth();
        
        // Get the first day of the week (Sunday = 0)
        $startDay = $firstDayOfMonth->dayOfWeek;
        
        // Calculate days to show (including previous month's days to fill the week)
        $daysInMonth = $lastDayOfMonth->day;
        $daysToShow = ceil(($daysInMonth + $startDay) / 7) * 7;
        
        $calendarDays = [];
        $currentDate = $firstDayOfMonth->copy()->subDays($startDay);
        $today = Carbon::today();
        $bookingScopeDays = $this->service->booking_scope_days ?? 30;
        $maxBookingDate = $today->copy()->addDays($bookingScopeDays);
        
        for ($i = 0; $i < $daysToShow; $i++) {
            $dayOfWeek = $currentDate->dayOfWeek;
            $dateString = $currentDate->format('Y-m-d');
            $isCurrentMonth = $currentDate->month == $this->currentMonth;
            $isToday = $currentDate->isToday();
            // Past means before today (not including today)
            $isPast = $currentDate->lt($today);
            // Beyond booking scope means after max booking date
            $isBeyondScope = $currentDate->gt($maxBookingDate);
            
            // Check if there's availability for this day (only for current/future dates in current month within booking scope)
            $hasAvailability = false;
            if ($isCurrentMonth && !$isPast && !$isBeyondScope) {
                if ($this->employee) {
                    // First check for employee-specific schedules
                    $employeeSchedules = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                        ->where('day_of_week', $dayOfWeek)
                        ->where('is_active', true)
                        ->where('employee_id', $this->employee->id)
                        ->exists();
                    
                    // If no employee schedules, fall back to user schedules (employee inherits from service owner)
                    if (!$employeeSchedules) {
                        $hasAvailability = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                            ->where('day_of_week', $dayOfWeek)
                            ->where('is_active', true)
                            ->where('user_id', $this->service->user_id)
                            ->whereNull('employee_id')
                            ->exists();
                    } else {
                        $hasAvailability = true;
                    }
                } else {
                    $hasAvailability = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                        ->where('day_of_week', $dayOfWeek)
                        ->where('is_active', true)
                        ->where('user_id', $this->service->user_id)
                        ->whereNull('employee_id')
                        ->exists();
                }
            }
            
            // Check if this date is selected - compare dates in the same format
            $isSelected = false;
            if (!empty($this->selectedDate)) {
                $selectedDateFormatted = Carbon::parse($this->selectedDate)->format('Y-m-d');
                $isSelected = $selectedDateFormatted === $dateString;
            }
            
            $calendarDays[] = [
                'date' => $dateString,
                'day' => $currentDate->day,
                'isCurrentMonth' => $isCurrentMonth,
                'isToday' => $isToday,
                'isPast' => $isPast,
                'isBeyondScope' => $isBeyondScope,
                'hasAvailability' => $hasAvailability,
                'isSelected' => $isSelected,
            ];
            
            $currentDate->addDay();
        }
        
        $this->calendarDays = $calendarDays;
        
        // Also generate the flat list of available dates for backward compatibility
        $this->generateAvailableDates();
    }

    protected function generateAvailableDates()
    {
        $dates = [];
        $startDate = Carbon::today();
        $bookingScopeDays = $this->service->booking_scope_days ?? 30;
        $endDate = Carbon::today()->addDays($bookingScopeDays);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek;

            // Check if there's availability for this day
            $availabilityQuery = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true);
            
            if ($this->employee) {
                // First check for employee-specific schedules
                $employeeSchedules = $availabilityQuery->where('employee_id', $this->employee->id)->exists();
                
                // If no employee schedules, fall back to user schedules (employee inherits from service owner)
                if (!$employeeSchedules) {
                    $hasAvailability = $availabilityQuery->where('user_id', $this->service->user_id)
                        ->whereNull('employee_id')
                        ->exists();
                } else {
                    $hasAvailability = true;
                }
            } else {
                $hasAvailability = $availabilityQuery->where('user_id', $this->service->user_id)
                    ->whereNull('employee_id')
                    ->exists();
            }

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
        $employeeId = $this->employee?->id;
        $allSlots = $this->availabilityService->getAvailableTimeSlots(
            $this->tenant->id,
            $this->service->id,
            $date,
            $employeeId
        );
        
        // Filter out past time slots if the selected date is today
        $coachTimezone = $this->getCoachTimezone();
        $selectedDate = Carbon::parse($date, $coachTimezone);
        $today = Carbon::today($coachTimezone);
        $now = Carbon::now($coachTimezone);
        
        if ($selectedDate->isToday()) {
            // Filter out time slots that have already passed
            $filteredSlots = array_filter($allSlots, function($slot) use ($date, $now, $coachTimezone) {
                $slotDateTime = Carbon::parse($date . ' ' . $slot['start'], $coachTimezone);
                return $slotDateTime->gt($now);
            });
            $this->availableTimeSlots = array_values($filteredSlots); // Re-index array
        } else {
            // For future dates, show all available slots
            $this->availableTimeSlots = $allSlots;
        }
        
        // If no available time slots, find the next date with available slots
        // Only do this if we're not already in a recursive call (prevent infinite loops)
        if (empty($this->availableTimeSlots) && $this->selectedDate === $date) {
            $nextAvailableDate = $this->findNextAvailableDate($date);
            if ($nextAvailableDate && $nextAvailableDate !== $date) {
                $nextDate = Carbon::parse($nextAvailableDate);
                
                // Update calendar month if needed
                if ($nextDate->month != $this->currentMonth || $nextDate->year != $this->currentYear) {
                    $this->currentMonth = $nextDate->month;
                    $this->currentYear = $nextDate->year;
                }
                
                // Set selected date and regenerate calendar to show it as selected
                $this->selectedDate = $nextAvailableDate;
                $this->generateCalendar();
                // Load time slots for the new date (this will set availableTimeSlots)
                $this->loadTimeSlots($nextAvailableDate);
            }
        }
    }
    
    protected function selectFirstAvailableDate()
    {
        $today = Carbon::today();
        $todayDateString = $today->format('Y-m-d');
        $now = Carbon::now();
        $bookingScopeDays = $this->service->booking_scope_days ?? 30;
        $endDate = $today->copy()->addDays($bookingScopeDays);
        $currentDate = $today->copy(); // Always start from today
        
        // First, check today specifically - if it has slots, always select today
        $todayDayOfWeek = $today->dayOfWeek;
        
        if ($this->employee) {
            // First check for employee-specific schedules
            $employeeSchedules = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                ->where('day_of_week', $todayDayOfWeek)
                ->where('is_active', true)
                ->where('employee_id', $this->employee->id)
                ->exists();
            
            // If no employee schedules, fall back to user schedules
            if (!$employeeSchedules) {
                $todayHasAvailability = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                    ->where('day_of_week', $todayDayOfWeek)
                    ->where('is_active', true)
                    ->where('user_id', $this->service->user_id)
                    ->whereNull('employee_id')
                    ->exists();
            } else {
                $todayHasAvailability = true;
            }
        } else {
            $todayHasAvailability = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                ->where('day_of_week', $todayDayOfWeek)
                ->where('is_active', true)
                ->where('user_id', $this->service->user_id)
                ->whereNull('employee_id')
                ->exists();
        }
        
        if ($todayHasAvailability) {
            $employeeId = $this->employee?->id;
            $todaySlots = $this->availabilityService->getAvailableTimeSlots(
                $this->tenant->id,
                $this->service->id,
                $todayDateString,
                $employeeId
            );
            
            // Filter out past slots
            $coachTimezone = $this->getCoachTimezone();
            $todaySlots = array_filter($todaySlots, function($slot) use ($todayDateString, $now, $coachTimezone) {
                $slotDateTime = Carbon::parse($todayDateString . ' ' . $slot['start'], $coachTimezone);
                return $slotDateTime->gt($now);
            });
            
            // If today has available slots, always select today
            if (!empty($todaySlots)) {
                // Update calendar month if needed
                if ($today->month != $this->currentMonth || $today->year != $this->currentYear) {
                    $this->currentMonth = $today->month;
                    $this->currentYear = $today->year;
                }
                
                // Set selected date to today
                $this->selectedDate = $todayDateString;
                // Generate calendar to show today as selected
                $this->generateCalendar();
                // Load time slots
                $this->loadTimeSlotsWithoutAutoMove($this->selectedDate);
                
                return;
            }
        }
        
        // If today doesn't have slots, find the next available date
        while ($currentDate->lte($endDate)) {
            // Skip today (already checked above) and past dates
            if ($currentDate->lt($today) || $currentDate->isToday()) {
                $currentDate->addDay();
                continue;
            }
            
            $dayOfWeek = $currentDate->dayOfWeek;
            
            // Check if there's availability for this day of week
            if ($this->employee) {
                // First check for employee-specific schedules
                $employeeSchedules = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->where('employee_id', $this->employee->id)
                    ->exists();
                
                // If no employee schedules, fall back to user schedules
                if (!$employeeSchedules) {
                    $hasAvailability = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                        ->where('day_of_week', $dayOfWeek)
                        ->where('is_active', true)
                        ->where('user_id', $this->service->user_id)
                        ->whereNull('employee_id')
                        ->exists();
                } else {
                    $hasAvailability = true;
                }
            } else {
                $hasAvailability = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->where('user_id', $this->service->user_id)
                    ->whereNull('employee_id')
                    ->exists();
            }
            
            if ($hasAvailability) {
                // Check if this date has available time slots
                $employeeId = $this->employee?->id;
                $slots = $this->availabilityService->getAvailableTimeSlots(
                    $this->tenant->id,
                    $this->service->id,
                    $currentDate->format('Y-m-d'),
                    $employeeId
                );
                
                // If we found slots, select this date
                if (!empty($slots)) {
                    // Update calendar month first if needed
                    if ($currentDate->month != $this->currentMonth || $currentDate->year != $this->currentYear) {
                        $this->currentMonth = $currentDate->month;
                        $this->currentYear = $currentDate->year;
                    }
                    
                    // Set selected date FIRST before generating calendar
                    $this->selectedDate = $currentDate->format('Y-m-d');
                    // Generate calendar to show the selected date
                    $this->generateCalendar();
                    // Load time slots - but don't auto-move if empty (already found slots above)
                    $this->loadTimeSlotsWithoutAutoMove($this->selectedDate);
                    
                    return;
                }
            }
            
            $currentDate->addDay();
        }
    }
    
    protected function loadTimeSlotsWithoutAutoMove(string $date)
    {
        $employeeId = $this->employee?->id;
        $allSlots = $this->availabilityService->getAvailableTimeSlots(
            $this->tenant->id,
            $this->service->id,
            $date,
            $employeeId
        );
        
        // Filter out past time slots if the selected date is today
        $coachTimezone = $this->getCoachTimezone();
        $selectedDate = Carbon::parse($date, $coachTimezone);
        $today = Carbon::today($coachTimezone);
        $now = Carbon::now($coachTimezone);
        
        if ($selectedDate->isToday()) {
            // Filter out time slots that have already passed
            $filteredSlots = array_filter($allSlots, function($slot) use ($date, $now, $coachTimezone) {
                $slotDateTime = Carbon::parse($date . ' ' . $slot['start'], $coachTimezone);
                return $slotDateTime->gt($now);
            });
            $this->availableTimeSlots = array_values($filteredSlots); // Re-index array
        } else {
            // For future dates, show all available slots
            $this->availableTimeSlots = $allSlots;
        }
    }
    
    protected function findNextAvailableDate(string $startDate): ?string
    {
        $start = Carbon::parse($startDate);
        $today = Carbon::today();
        $endDate = $today->copy()->addDays(90); // Search up to 90 days ahead
        
        // Start from the day after the selected date (or today if selected date is in the past)
        $currentDate = $start->copy()->addDay();
        if ($currentDate->lt($today)) {
            $currentDate = $today->copy();
        }
        
        while ($currentDate->lte($endDate)) {
            $dayOfWeek = $currentDate->dayOfWeek;
            
            // Check if there's availability for this day of week
            if ($this->employee) {
                // First check for employee-specific schedules
                $employeeSchedules = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->where('employee_id', $this->employee->id)
                    ->exists();
                
                // If no employee schedules, fall back to user schedules
                if (!$employeeSchedules) {
                    $hasAvailability = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                        ->where('day_of_week', $dayOfWeek)
                        ->where('is_active', true)
                        ->where('user_id', $this->service->user_id)
                        ->whereNull('employee_id')
                        ->exists();
                } else {
                    $hasAvailability = true;
                }
            } else {
                $hasAvailability = \App\Models\AvailabilitySchedule::where('tenant_id', $this->tenant->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->where('user_id', $this->service->user_id)
                    ->whereNull('employee_id')
                    ->exists();
            }
            
            if ($hasAvailability) {
                // Check if this date has available time slots
                $employeeId = $this->employee?->id;
                $slots = $this->availabilityService->getAvailableTimeSlots(
                    $this->tenant->id,
                    $this->service->id,
                    $currentDate->format('Y-m-d'),
                    $employeeId
                );
                
                // Filter out past slots if today
                if ($currentDate->isToday()) {
                    $now = Carbon::now();
                    $slots = array_filter($slots, function($slot) use ($currentDate, $now) {
                        $slotDateTime = Carbon::parse($currentDate->format('Y-m-d') . ' ' . $slot['start']);
                        return $slotDateTime->gt($now);
                    });
                }
                
                // If we found slots, return this date
                if (!empty($slots)) {
                    return $currentDate->format('Y-m-d');
                }
            }
            
            $currentDate->addDay();
        }
        
        return null; // No available date found
    }

    public function getRemainingSpotsProperty(): ?int
    {
        if (!$this->selectedDate || !$this->selectedTime || $this->service->type !== 'group') {
            return null;
        }

        $coachTimezone = $this->getCoachTimezone();
        $dateTime = Carbon::parse($this->selectedDate . ' ' . $this->selectedTime, $coachTimezone);
        $employeeId = $this->employee?->id;
        return $this->availabilityService->getRemainingSpots(
            $this->tenant->id,
            $this->service->id,
            $dateTime,
            $employeeId
        );
    }

    public function render()
    {
        return view('livewire.booking.show')
            ->layout('livewire.layouts.booking-layout', [
                'title' => $this->service ? $this->service->translated_name . ' - ' . __('common.book_appointment') : __('common.book_appointment'),
            ]);
    }
}
