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
Route::get('/{tenantSlug}/{serviceSlug}', Show::class)
    ->name('booking.show');
