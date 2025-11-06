<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div>
            <a href="/" class="flex justify-center">
                <span class="text-3xl font-bold text-gray-900">{{ __('auth.logo') }}</span>
            </a>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                {{ __('auth.login_title') }}
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                {{ __('auth.login_subtitle') }}
                <a href="{{ route('register') }}" class="font-medium text-blue-600 hover:text-blue-500">
                    {{ __('auth.sign_up_link') }}
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
        <form class="mt-8 space-y-6" wire:submit="login">
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

            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email" class="sr-only">{{ __('auth.email') }}</label>
                    <input 
                        id="email" 
                        name="email" 
                        type="email" 
                        autocomplete="email" 
                        required 
                        wire:model="email"
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('email') border-red-500 @enderror" 
                        placeholder="{{ __('auth.email_placeholder') }}"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password" class="sr-only">{{ __('auth.password') }}</label>
                    <input 
                        id="password" 
                        name="password" 
                        type="password" 
                        autocomplete="current-password" 
                        required 
                        wire:model="password"
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('password') border-red-500 @enderror" 
                        placeholder="{{ __('auth.password_placeholder') }}"
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input 
                        id="remember" 
                        name="remember" 
                        type="checkbox" 
                        wire:model="remember"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    >
                    <label for="remember" class="ml-2 rtl:ml-0 rtl:mr-2 block text-sm text-gray-900">
                        {{ __('auth.remember_me') }}
                    </label>
                </div>

                <div class="text-sm">
                    <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                        {{ __('auth.forgot_password') }}
                    </a>
                </div>
            </div>

            <div>
                <button 
                    type="submit" 
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    {{ __('auth.login_button') }}
                </button>
            </div>
        </form>
    </div>
</div>

