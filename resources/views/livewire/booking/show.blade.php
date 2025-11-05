<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                @if($service)
                    {{ $service->name }}
                @endif
            </h1>
            @if($service && $service->description)
                <p class="text-gray-600">{{ $service->description }}</p>
            @endif
        </div>

        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600' }}">
                            <span class="font-semibold">1</span>
                        </div>
                        <span class="ml-2 text-sm font-medium {{ $currentStep >= 1 ? 'text-blue-600' : 'text-gray-500' }}">Select Date & Time</span>
                    </div>
                    <div class="w-16 h-1 {{ $currentStep >= 2 ? 'bg-blue-600' : 'bg-gray-300' }}"></div>
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600' }}">
                            <span class="font-semibold">2</span>
                        </div>
                        <span class="ml-2 text-sm font-medium {{ $currentStep >= 2 ? 'text-blue-600' : 'text-gray-500' }}">Your Information</span>
                    </div>
                    <div class="w-16 h-1 {{ $currentStep >= 3 ? 'bg-blue-600' : 'bg-gray-300' }}"></div>
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600' }}">
                            <span class="font-semibold">3</span>
                        </div>
                        <span class="ml-2 text-sm font-medium {{ $currentStep >= 3 ? 'text-blue-600' : 'text-gray-500' }}">Confirmation</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 1: Date & Time Selection -->
        @if($currentStep == 1)
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Select Date & Time</h2>

                @if($service)
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Duration:</span>
                                <span class="font-semibold ml-2">{{ $service->duration }} minutes</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Price:</span>
                                <span class="font-semibold ml-2">EGP {{ number_format($service->price, 2) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Type:</span>
                                <span class="font-semibold ml-2">{{ $service->type === 'one' ? 'One-to-One' : 'Group' }}</span>
                            </div>
                            @if($service->type === 'group' && $service->max_spots)
                                <div>
                                    <span class="text-gray-600">Max Spots:</span>
                                    <span class="font-semibold ml-2">{{ $service->max_spots }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Date Selection -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-3">Select a Date</h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        @foreach($availableDates as $date)
                            <button
                                type="button"
                                wire:click="selectDate('{{ $date['date'] }}')"
                                class="p-4 border-2 rounded-lg text-center transition {{ $selectedDate === $date['date'] ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-blue-300' }}"
                            >
                                <div class="text-sm text-gray-600">{{ $date['day'] }}</div>
                                <div class="font-semibold mt-1">{{ $date['display'] }}</div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Time Slots -->
                @if($selectedDate)
                    <div>
                        <h3 class="text-lg font-medium mb-3">Available Time Slots</h3>
                        @if(empty($availableTimeSlots))
                            <div class="text-center py-8 text-gray-500">
                                <p>No available time slots for this date.</p>
                                <p class="text-sm mt-2">Please select another date.</p>
                            </div>
                        @else
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                @foreach($availableTimeSlots as $slot)
                                    <button
                                        type="button"
                                        wire:click="selectTime('{{ $slot['start'] }}')"
                                        class="p-3 border-2 rounded-lg text-center transition {{ $selectedTime === $slot['start'] ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-blue-300' }}"
                                    >
                                        <div class="font-semibold">{{ $slot['display'] }}</div>
                                        @if($service && $service->type === 'group' && $this->remainingSpots)
                                            <div class="text-xs text-gray-600 mt-1">
                                                {{ $this->remainingSpots }} spots left
                                            </div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        <!-- Step 2: Client Information -->
        @if($currentStep == 2)
            <div class="bg-white rounded-lg shadow-md p-6">
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
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
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
