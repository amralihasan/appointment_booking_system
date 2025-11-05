@php
    $record = $getRecord();
    
    // Get all time slots for this day
    $timeSlots = \App\Models\AvailabilitySchedule::where('tenant_id', $record->tenant_id)
        ->where('user_id', $record->user_id)
        ->where('day_of_week', $record->day_of_week)
        ->orderBy('start_time')
        ->get();
@endphp

<div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
    <div class="space-y-2">
        @foreach($timeSlots as $slot)
            <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-900 rounded-md border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }}
                        </span>
                        <span class="text-gray-400">-</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }}
                        </span>
                    </div>
                    
                    @if($slot->is_active)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            Inactive
                        </span>
                    @endif
                </div>
                
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Duration: {{ \Carbon\Carbon::parse($slot->start_time)->diffInMinutes(\Carbon\Carbon::parse($slot->end_time)) }} min
                    </span>
                </div>
            </div>
        @endforeach
        
        @if($timeSlots->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                No time slots configured for this day.
            </p>
        @endif
    </div>
</div>

