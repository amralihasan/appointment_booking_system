<div class="min-h-screen bg-white">
    <!-- Navigation -->
    <nav class="border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-gray-900">
                        {{ __('landing.logo') }}
                    </a>
                </div>
                <div class="flex items-center space-x-4 rtl:space-x-reverse">
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                        {{ __('landing.sign_in') }}
                    </a>
                    <a href="{{ route('register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                        {{ __('landing.get_started') }}
                    </a>
                    <div class="flex items-center space-x-2 rtl:space-x-reverse ml-4 rtl:ml-0 rtl:mr-4">
                        <a href="{{ request()->fullUrlWithQuery(['locale' => 'en']) }}" class="text-sm {{ app()->getLocale() === 'en' ? 'font-bold text-blue-600' : 'text-gray-500' }}">EN</a>
                        <span class="text-gray-300">|</span>
                        <a href="{{ request()->fullUrlWithQuery(['locale' => 'ar']) }}" class="text-sm {{ app()->getLocale() === 'ar' ? 'font-bold text-blue-600' : 'text-gray-500' }}">AR</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6">
                {{ __('landing.hero_title') }}
            </h1>
            <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                {{ __('landing.hero_subtitle') }}
            </p>
            <div class="flex justify-center space-x-4 rtl:space-x-reverse">
                <a href="{{ route('register') }}" class="bg-blue-600 text-white px-8 py-4 rounded-lg text-lg font-medium hover:bg-blue-700 transition shadow-lg">
                    {{ __('landing.start_free_trial') }}
                </a>
                <a href="#features" class="bg-white text-blue-600 border-2 border-blue-600 px-8 py-4 rounded-lg text-lg font-medium hover:bg-blue-50 transition">
                    {{ __('landing.learn_more') }}
                </a>
            </div>
            <div class="mt-12">
                <p class="text-sm text-gray-500 mb-4">{{ __('landing.no_credit_card') }}</p>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    {{ __('landing.features_title') }}
                </h2>
                <p class="text-xl text-gray-600">
                    {{ __('landing.features_subtitle') }}
                </p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white p-8 rounded-lg shadow-sm">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">
                        {{ __('landing.feature1_title') }}
                    </h3>
                    <p class="text-gray-600">
                        {{ __('landing.feature1_description') }}
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white p-8 rounded-lg shadow-sm">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">
                        {{ __('landing.feature2_title') }}
                    </h3>
                    <p class="text-gray-600">
                        {{ __('landing.feature2_description') }}
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white p-8 rounded-lg shadow-sm">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">
                        {{ __('landing.feature3_title') }}
                    </h3>
                    <p class="text-gray-600">
                        {{ __('landing.feature3_description') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-blue-600 to-blue-700">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-4xl font-bold text-white mb-6">
                {{ __('landing.cta_title') }}
            </h2>
            <p class="text-xl text-blue-100 mb-8">
                {{ __('landing.cta_subtitle') }}
            </p>
            <a href="{{ route('register') }}" class="inline-block bg-white text-blue-600 px-8 py-4 rounded-lg text-lg font-medium hover:bg-gray-100 transition shadow-lg">
                {{ __('landing.get_started_now') }}
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-white font-bold text-lg mb-4">{{ __('landing.logo') }}</h3>
                    <p class="text-sm">{{ __('landing.footer_description') }}</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">{{ __('landing.product') }}</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">{{ __('landing.features') }}</a></li>
                        <li><a href="#" class="hover:text-white">{{ __('landing.pricing') }}</a></li>
                        <li><a href="#" class="hover:text-white">{{ __('landing.integrations') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">{{ __('landing.company') }}</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">{{ __('landing.about') }}</a></li>
                        <li><a href="#" class="hover:text-white">{{ __('landing.contact') }}</a></li>
                        <li><a href="#" class="hover:text-white">{{ __('landing.blog') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">{{ __('landing.support') }}</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">{{ __('landing.help_center') }}</a></li>
                        <li><a href="#" class="hover:text-white">{{ __('landing.documentation') }}</a></li>
                        <li><a href="#" class="hover:text-white">{{ __('landing.api') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
                <p>&copy; {{ date('Y') }} {{ __('landing.logo') }}. {{ __('landing.all_rights_reserved') }}.</p>
            </div>
        </div>
    </footer>
</div>

