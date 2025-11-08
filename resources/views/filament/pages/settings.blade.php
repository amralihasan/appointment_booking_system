<x-filament-panels::page>
    <div class="flex gap-6">
        <!-- Vertical Tabs Navigation -->
        <div class="w-64 flex-shrink-0">
            <nav class="space-y-1" aria-label="Settings">
                <a
                    wire:click="setActiveTab('profile')"
                    href="#"
                    @class([
                        'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
                        'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' => $activeTab === 'profile',
                        'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white' => $activeTab !== 'profile',
                    ])
                >
                    <x-heroicon-o-user-circle
                        @class([
                            'mr-3 h-5 w-5 flex-shrink-0',
                            'text-primary-500' => $activeTab === 'profile',
                            'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400' => $activeTab !== 'profile',
                        ])
                    />
                    {{ __('filament.profile') }}
                </a>

                <a
                    wire:click="setActiveTab('tenant')"
                    href="#"
                    @class([
                        'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
                        'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' => $activeTab === 'tenant',
                        'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white' => $activeTab !== 'tenant',
                    ])
                >
                    <x-heroicon-o-building-office
                        @class([
                            'mr-3 h-5 w-5 flex-shrink-0',
                            'text-primary-500' => $activeTab === 'tenant',
                            'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400' => $activeTab !== 'tenant',
                        ])
                    />
                    {{ __('filament.tenant_information') }}
                </a>

                <a
                    wire:click="setActiveTab('currency')"
                    href="#"
                    @class([
                        'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
                        'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' => $activeTab === 'currency',
                        'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white' => $activeTab !== 'currency',
                    ])
                >
                    <x-heroicon-o-currency-dollar
                        @class([
                            'mr-3 h-5 w-5 flex-shrink-0',
                            'text-primary-500' => $activeTab === 'currency',
                            'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400' => $activeTab !== 'currency',
                        ])
                    />
                    {{ __('filament.currency') }}
                </a>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="flex-1">
            @if($activeTab === 'profile')
                <div>
                    <h2 class="text-xl font-semibold mb-4">{{ __('filament.profile') }}</h2>
                    <livewire:profile-form />
                </div>
            @elseif($activeTab === 'tenant')
                <div>
                    <h2 class="text-xl font-semibold mb-4">{{ __('filament.tenant_information') }}</h2>
                    <livewire:tenant-profile-form />
                </div>
            @elseif($activeTab === 'currency')
                <div>
                    <h2 class="text-xl font-semibold mb-4">{{ __('filament.currency') }}</h2>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('filament.currency_settings_description') }}</p>
                    <!-- Currency settings will be added here later -->
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
