<div class="min-h-screen bg-gray-50 flex items-center justify-center py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl w-full mx-auto">
        <!-- Header -->
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-1">
                @if($service)
                    {{ $service->name }}
                @endif
            </h1>
            @if($service && $service->description)
                <p class="text-sm text-gray-600">{{ $service->description }}</p>
            @endif
        </div>

        <!-- Step 1: Date & Time Selection -->
        @if($currentStep == 1)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-0">
                    <!-- Left Side: Calendar -->
                    <div class="p-3 border-r border-gray-200">
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <button
                                    type="button"
                                    wire:click="previousMonth"
                                    class="p-1 hover:bg-gray-100 rounded-lg transition"
                                >
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <h3 class="text-base font-semibold text-gray-900">
                                    {{ Carbon\Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }}
                                </h3>
                                <button
                                    type="button"
                                    wire:click="nextMonth"
                                    class="p-1 hover:bg-gray-100 rounded-lg transition"
                                >
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Day Headers -->
                            <div class="grid grid-cols-7 gap-2 mb-2">
                                @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                                    <div class="text-center text-xs font-medium text-gray-500 py-1">
                                        {{ $day }}
                                    </div>
                                @endforeach
                            </div>

                            <!-- Calendar Grid -->
                            <div class="grid grid-cols-7 gap-2">
                                @foreach($calendarDays as $day)
                                    <button
                                        type="button"
                                        @if($day['isCurrentMonth'] && !$day['isPast'] && $day['hasAvailability'])
                                            wire:click="selectDate('{{ $day['date'] }}')"
                                        @endif
                                        class="aspect-square flex items-center justify-center text-base font-normal rounded-lg transition
                                            @if(!$day['isCurrentMonth'] || $day['isPast'])
                                                text-gray-300 cursor-not-allowed bg-transparent
                                            @elseif($day['isSelected'])
                                                bg-blue-600 text-white font-medium
                                            @elseif($day['isToday'])
                                                bg-gray-100 text-gray-700 font-medium
                                            @elseif($day['hasAvailability'])
                                                text-gray-700 hover:bg-gray-100 cursor-pointer bg-transparent
                                            @else
                                                text-gray-400 cursor-not-allowed bg-transparent
                                            @endif
                                        "
                                        @if($day['isSelected'])
                                            wire:key="selected-{{ $day['date'] }}"
                                        @endif
                                        @if(!$day['isCurrentMonth'] || $day['isPast'] || !$day['hasAvailability'])
                                            disabled
                                        @endif
                                    >
                                        {{ $day['day'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Service Info -->
                        @if($service)
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <div class="space-y-1 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Duration:</span>
                                        <span class="font-semibold">{{ $service->duration }} min</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Price:</span>
                                        <span class="font-semibold">EGP {{ number_format($service->price, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Type:</span>
                                        <span class="font-semibold">{{ $service->type === 'one' ? 'One-to-One' : 'Group' }}</span>
                                    </div>
                                    @if($service->type === 'group' && $service->max_spots)
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Max Spots:</span>
                                            <span class="font-semibold">{{ $service->max_spots }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Right Side: Time Slots -->
                    <div class="p-4 bg-gray-50 flex flex-col" style="height: 500px;">
                        @if($selectedDate)
                            <!-- Fixed Header -->
                            <div class="mb-3 flex-shrink-0">
                                <h3 class="text-base font-semibold text-gray-900 mb-1">
                                    {{ Carbon\Carbon::parse($selectedDate)->format('l, F d, Y') }}
                                </h3>
                                <p class="text-xs text-gray-600">Select a time slot</p>
                            </div>

                            @if(empty($availableTimeSlots))
                                <div class="text-center py-8 flex-shrink-0">
                                    <svg class="mx-auto h-10 w-10 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-sm text-gray-500 font-medium">No available time slots</p>
                                    <p class="text-xs text-gray-400 mt-1">Please select another date</p>
                                </div>
                            @else
                                <!-- Scrollable Time Slots List -->
                                <div class="space-y-1.5 overflow-y-auto flex-1 min-h-0">
                                    @foreach($availableTimeSlots as $slot)
                                        @php
                                            $slotDateTime = \Carbon\Carbon::parse($selectedDate . ' ' . $slot['start']);
                                            $isPast = $slotDateTime->lt(\Carbon\Carbon::now());
                                        @endphp
                                        <button
                                            type="button"
                                            @if(!$isPast)
                                                wire:click="selectTime('{{ $slot['start'] }}')"
                                            @endif
                                            class="w-full p-2.5 text-left border-2 rounded-lg transition text-sm
                                                @if($isPast)
                                                    border-gray-100 bg-gray-50 text-gray-400 cursor-not-allowed opacity-50
                                                @elseif($selectedTime === $slot['start'])
                                                    border-blue-600 bg-blue-50 text-blue-700
                                                @else
                                                    border-gray-200 hover:border-blue-300 hover:bg-blue-50 text-gray-700 cursor-pointer
                                                @endif
                                            "
                                            @if($isPast)
                                                disabled
                                            @endif
                                        >
                                            <div class="flex items-center justify-between">
                                                <span class="font-medium text-sm">{{ $slot['display'] }}</span>
                                                @if($isPast)
                                                    <span class="text-xs text-gray-400">Past</span>
                                                @elseif($service && $service->type === 'group')
                                                    <span class="text-xs text-gray-500">
                                                        @if($this->remainingSpots)
                                                            {{ $this->remainingSpots }} spots left
                                                        @else
                                                            Full
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-10 w-10 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <p class="text-sm text-gray-500 font-medium">Select a date</p>
                                <p class="text-xs text-gray-400 mt-1">Choose a date from the calendar to view available time slots</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Step 2: Client Information -->
        @if($currentStep == 2)
            <div class="bg-white rounded-lg shadow-md p-6 max-w-2xl mx-auto">
                <h2 class="text-xl font-semibold mb-4">Your Information</h2>

                <div class="mb-4 p-3 bg-blue-50 rounded-lg">
                    <p class="text-sm text-gray-700">
                        <strong>Selected:</strong> {{ \Carbon\Carbon::parse($selectedDate)->format('M d, Y') }} at {{ \Carbon\Carbon::parse($selectedTime)->format('g:i A') }}
                    </p>
                </div>

                <form wire:submit.prevent="submitBooking">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    First Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model.blur="clientFirstName"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                @error('clientFirstName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Last Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model.blur="clientLastName"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                @error('clientLastName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Phone <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="tel"
                                wire:model.blur="clientPhone"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            >
                            @error('clientPhone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Email (Optional)
                            </label>
                            <input
                                type="email"
                                wire:model.blur="clientEmail"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                            @error('clientEmail') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Additional Notes (Optional)
                            </label>
                            <textarea
                                wire:model.blur="notes"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            ></textarea>
                        </div>
                    </div>

                    @error('booking') 
                        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                            <p class="text-red-600 text-sm">{{ $message }}</p>
                        </div>
                    @enderror

                    <div class="mt-6 flex justify-between">
                        <button
                            type="button"
                            wire:click="goBackToStep1"
                            class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition"
                        >
                            Back
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
                        >
                            Confirm Booking
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Step 3: Confirmation -->
        @if($currentStep == 3 && $appointment)
            <div class="bg-white rounded-lg shadow-md p-6 text-center max-w-2xl mx-auto">
                <div class="mb-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Booking Confirmed!</h2>
                    <p class="text-gray-600">Your appointment has been successfully booked.</p>
                </div>

                <div class="bg-gray-50 rounded-lg p-6 mb-6 text-left">
                    <h3 class="font-semibold mb-4">Booking Details</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Service:</span>
                            <span class="font-semibold">{{ $service->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Date & Time:</span>
                            <span class="font-semibold">{{ $appointment->date_time->format('M d, Y g:i A') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Duration:</span>
                            <span class="font-semibold">{{ $appointment->duration }} minutes</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Client:</span>
                            <span class="font-semibold">{{ $appointment->client_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Phone:</span>
                            <span class="font-semibold">{{ $appointment->client_phone }}</span>
                        </div>
                        @if($appointment->client_email)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Email:</span>
                                <span class="font-semibold">{{ $appointment->client_email }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="font-semibold text-green-600">Booked</span>
                        </div>
                    </div>
                </div>

                <div class="text-sm text-gray-600">
                    <p>You will receive a confirmation email shortly.</p>
                    <p class="mt-2">If you need to make changes, please contact the coach directly.</p>
                </div>
            </div>
        @endif
    </div>
</div>
