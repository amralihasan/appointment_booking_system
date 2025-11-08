<div class="space-y-4">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        {{ __('filament.select_employee_to_book_description') }}
    </p>
    
    <div class="space-y-3">
        @foreach($employees as $employee)
            <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                <div class="flex items-center space-x-3">
                    <img 
                        src="{{ $employee->photo_url }}" 
                        alt="{{ $employee->full_name }}" 
                        class="w-8 h-8 rounded-full object-cover"
                    >
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                            {{ $employee->full_name }}
                        </h3>
                        @if($employee->bio)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ Str::limit($employee->bio, 50) }}
                            </p>
                        @endif
                    </div>
                </div>
                <a 
                    href="{{ url('/' . $tenant->slug . '/' . $service->slug . '/' . $employee->slug) }}" 
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    {{ __('filament.book_now') }}
                </a>
            </div>
        @endforeach
    </div>
</div>

