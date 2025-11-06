<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from user preference if authenticated
        $locale = 'en'; // default
        
        if (auth()->check()) {
            $user = auth()->user();
            $locale = $user->language ?? 'en';
        } else {
            // Check session or accept-language header
            $locale = session('locale', $request->getPreferredLanguage(['en', 'ar']) ?? 'en');
        }
        
        // Ensure locale is valid (en or ar)
        if (!in_array($locale, ['en', 'ar'])) {
            $locale = 'en';
        }
        
        App::setLocale($locale);
        $request->setLocale($locale);
        
        // Set Carbon locale for date formatting
        \Carbon\Carbon::setLocale($locale);
        
        return $next($request);
    }
}
