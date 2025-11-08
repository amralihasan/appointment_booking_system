<div class="space-y-4">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        {{ __('filament.select_employee_to_book_description') }}
    </p>
    
    <!-- Share Link Section -->
    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('filament.share_service_link') }}
                </label>
                <div class="flex items-center space-x-2">
                    <input 
                        type="text" 
                        id="service-share-link-{{ $service->id }}" 
                        value="{{ url('/' . $tenant->slug . '/' . $service->slug . '/employees') }}" 
                        readonly
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                    <button 
                        type="button"
                        id="copy-service-link-btn-{{ $service->id }}"
                        class="px-4 py-2 text-sm bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors"
                    >
                        <span id="copy-service-link-text-{{ $service->id }}">{{ __('filament.copy_link') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
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

<script>
(function() {
    // Use a unique function name to avoid conflicts
    function initCopyButton{{ $service->id }}() {
        const buttonId = 'copy-service-link-btn-{{ $service->id }}';
        const inputId = 'service-share-link-{{ $service->id }}';
        const textId = 'copy-service-link-text-{{ $service->id }}';
        
        // Try to find the button - might need to wait for modal to be fully loaded
        function attachListener() {
            const button = document.getElementById(buttonId);
            const input = document.getElementById(inputId);
            const buttonText = document.getElementById(textId);
            
            if (button && input && buttonText) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    input.select();
                    input.setSelectionRange(0, 99999);
                    const text = input.value;
                    
                    const originalText = buttonText.textContent;
                    const originalClasses = button.className;
                    
                    // Try modern clipboard API first
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(() => {
                            // Success - change to green
                            showCopySuccess(button, buttonText, originalText, originalClasses);
                        }).catch(() => {
                            // Fallback to execCommand
                            fallbackCopy{{ $service->id }}(text, button, buttonText, originalText, originalClasses);
                        });
                    } else {
                        // Fallback to execCommand
                        fallbackCopy{{ $service->id }}(text, button, buttonText, originalText, originalClasses);
                    }
                });
                return true;
            }
            return false;
        }
        
        // Try immediately
        if (!attachListener()) {
            // If not found, wait a bit and try again (modal might not be fully loaded)
            setTimeout(function() {
                if (!attachListener()) {
                    // Try one more time after a longer delay
                    setTimeout(attachListener, 500);
                }
            }, 100);
        }
    }
    
    function showCopySuccess(button, buttonText, originalText, originalClasses) {
        // Change text
        buttonText.textContent = '{{ __('filament.copied') }}';
        
        // Remove primary colors and add green colors
        button.classList.remove('bg-primary-600', 'hover:bg-primary-700');
        button.classList.add('bg-green-600', 'hover:bg-green-700');
        
        setTimeout(() => {
            // Restore original text
            buttonText.textContent = originalText;
            
            // Remove green colors and restore primary colors
            button.classList.remove('bg-green-600', 'hover:bg-green-700');
            button.classList.add('bg-primary-600', 'hover:bg-primary-700');
        }, 2000);
    }
    
    function fallbackCopy{{ $service->id }}(text, button, buttonText, originalText, originalClasses) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            if (document.execCommand('copy')) {
                // Success - change to green
                showCopySuccess(button, buttonText, originalText, originalClasses);
            }
        } catch (err) {
            console.error('Failed to copy text:', err);
        } finally {
            document.body.removeChild(textArea);
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCopyButton{{ $service->id }});
    } else {
        initCopyButton{{ $service->id }}();
    }
    
    // Also try after a delay in case the modal loads later
    setTimeout(initCopyButton{{ $service->id }}, 500);
})();
</script>

