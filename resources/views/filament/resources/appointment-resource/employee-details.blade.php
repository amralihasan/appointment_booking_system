@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;
@endphp
<div class="space-y-6">
    <!-- Employee Information -->
    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
            {{ __('filament.employee_information') }}
        </h3>
        <div class="flex items-start space-x-4">
            <img 
                src="{{ $employee->photo_url }}" 
                alt="{{ $employee->full_name }}" 
                class="w-16 h-16 rounded-full object-cover"
            >
            <div class="flex-1">
                <h4 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $employee->full_name }}
                </h4>
                @if($employee->bio)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $employee->bio }}
                    </p>
                @endif
                <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                    @if($employee->email)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            {{ $employee->email }}
                        </div>
                    @endif
                    @if($employee->phone)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            {{ $employee->phone }}
                        </div>
                    @endif
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ $employee->timezone }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Appointments -->
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
            {{ __('filament.appointments') }} ({{ $appointments->count() }})
        </h3>
        @if($appointments->count() > 0)
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @foreach($appointments as $appointment)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ Carbon::parse($appointment->date_time)->format('M d, Y') }}
                                    </span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ Carbon::parse($appointment->date_time)->format('g:i A') }}
                                    </span>
                                    <span class="px-2 py-1 text-xs rounded-full {{ $appointment->status === 'booked' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($appointment->status === 'canceled' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:bg-blue-200') }}">
                                        {{ __('filament.' . $appointment->status) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <strong>{{ __('filament.service_name') }}:</strong> {{ $appointment->service->name ?? 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <strong>{{ __('filament.client_name') }}:</strong> {{ $appointment->client_name }}
                                </p>
                                @if($appointment->client_phone)
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <strong>{{ __('filament.client_phone') }}:</strong> {{ $appointment->client_phone }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('filament.no_appointments_found') }}
            </p>
        @endif
    </div>
</div>

