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
        // Get locale from query parameter first (for language switcher)
        $locale = $request->query('locale');
        
        // If no query parameter, get from user preference if authenticated
        if (!$locale && auth()->check()) {
            $user = auth()->user();
            $locale = $user->language ?? null;
        }
        
        // If still no locale, check session or accept-language header
        if (!$locale) {
            $locale = session('locale', $request->getPreferredLanguage(['en', 'ar']) ?? 'en');
        }
        
        // Ensure locale is valid (en or ar)
        if (!in_array($locale, ['en', 'ar'])) {
            $locale = 'en';
        }
        
        // Store in session for persistence
        session(['locale' => $locale]);
        
        App::setLocale($locale);
        $request->setLocale($locale);
        
        // Set Carbon locale for date formatting
        \Carbon\Carbon::setLocale($locale);
        
        return $next($request);
    }
}
