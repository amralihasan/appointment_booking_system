<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Booking\Show;
use App\Livewire\LandingPage;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPage::class)
    ->name('landing');

Route::get('/login', Login::class)
    ->name('login')
    ->middleware('guest');

Route::get('/register', Register::class)
    ->name('register')
    ->middleware('guest');

// Public booking route: domain-name.coach-name/service-name
// Exclude admin routes to avoid conflicts
Route::get('/{tenantSlug}/{serviceSlug}', Show::class)
    ->where('tenantSlug', '[a-z0-9\-]+')
    ->where('serviceSlug', '[a-z0-9\-]+')
    ->name('booking.show')
    ->middleware('web');
