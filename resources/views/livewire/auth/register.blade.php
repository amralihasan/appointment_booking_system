<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div>
            <a href="/" class="flex justify-center">
                <span class="text-3xl font-bold text-gray-900">{{ __('auth.logo') }}</span>
            </a>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                {{ __('auth.register_title') }}
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                {{ __('auth.register_subtitle') }}
                <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">
                    {{ __('auth.sign_in_link') }}
                </a>
            </p>
        </div>

        <!-- Language Switcher -->
        <div class="flex justify-center space-x-2 rtl:space-x-reverse">
            <a href="{{ request()->fullUrlWithQuery(['locale' => 'en']) }}" class="text-sm {{ app()->getLocale() === 'en' ? 'font-bold text-blue-600' : 'text-gray-500' }}">EN</a>
            <span class="text-gray-300">|</span>
            <a href="{{ request()->fullUrlWithQuery(['locale' => 'ar']) }}" class="text-sm {{ app()->getLocale() === 'ar' ? 'font-bold text-blue-600' : 'text-gray-500' }}">AR</a>
        </div>

        <!-- Form -->
        <form class="mt-8 space-y-6" wire:submit="register">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 rtl:ml-0 rtl:mr-3">
                            <h3 class="text-sm font-medium text-red-800">
                                {{ __('auth.validation_errors') }}
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="space-y-4">
                <!-- Category -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Business Category
                    </label>
                    <select 
                        id="category_id" 
                        name="category_id" 
                        required 
                        wire:model="category_id"
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('category_id') border-red-500 @enderror"
                    >
                        <option value="">Select your business category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tenant Name -->
                <div>
                    <label for="tenant_name" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('auth.tenant_name') }}
                    </label>
                    <input 
                        id="tenant_name" 
                        name="tenant_name" 
                        type="text" 
                        required 
                        wire:model.live="tenant_name"
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('tenant_name') border-red-500 @enderror" 
                        placeholder="{{ __('auth.tenant_name_placeholder') }}"
                    >
                    @error('tenant_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tenant Slug -->
                <div>
                    <label for="tenant_slug" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('auth.tenant_slug') }}
                    </label>
                    <input 
                        id="tenant_slug" 
                        name="tenant_slug" 
                        type="text" 
                        required 
                        wire:model="tenant_slug"
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('tenant_slug') border-red-500 @enderror" 
                        placeholder="{{ __('auth.tenant_slug_placeholder') }}"
                    >
                    <p class="mt-1 text-sm text-gray-500">{{ __('auth.tenant_slug_helper') }}</p>
                    @error('tenant_slug')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- First Name -->
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('auth.first_name') }}
                    </label>
                    <input 
                        id="first_name" 
                        name="first_name" 
                        type="text" 
                        required 
                        wire:model="first_name"
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('first_name') border-red-500 @enderror" 
                        placeholder="{{ __('auth.first_name_placeholder') }}"
                    >
                    @error('first_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Last Name -->
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('auth.last_name') }}
                    </label>
                    <input 
                        id="last_name" 
                        name="last_name" 
                        type="text" 
                        required 
                        wire:model="last_name"
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('last_name') border-red-500 @enderror" 
                        placeholder="{{ __('auth.last_name_placeholder') }}"
                    >
                    @error('last_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('auth.email') }}
                    </label>
                    <input 
                        id="email" 
                        name="email" 
                        type="email" 
                        required 
                        wire:model="email"
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') border-red-500 @enderror" 
                        placeholder="{{ __('auth.email_placeholder') }}"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Mobile -->
                <div>
                    <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('auth.mobile') }}
                    </label>
                    <input 
                        id="mobile" 
                        name="mobile" 
                        type="tel" 
                        required 
                        wire:model="mobile"
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('mobile') border-red-500 @enderror" 
                        placeholder="{{ __('auth.mobile_placeholder') }}"
                    >
                    @error('mobile')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('auth.password') }}
                    </label>
                    <input 
                        id="password" 
                        name="password" 
                        type="password" 
                        required 
                        wire:model="password"
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password') border-red-500 @enderror" 
                        placeholder="{{ __('auth.password_placeholder') }}"
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Confirmation -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('auth.password_confirmation') }}
                    </label>
                    <input 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        type="password" 
                        required 
                        wire:model="password_confirmation"
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password_confirmation') border-red-500 @enderror" 
                        placeholder="{{ __('auth.password_confirmation_placeholder') }}"
                    >
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <button 
                    type="submit" 
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    {{ __('auth.register_button') }}
                </button>
            </div>
        </form>
    </div>
</div>

