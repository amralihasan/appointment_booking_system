<?php

use App\Livewire\Booking\Show;
use App\Livewire\LandingPage;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPage::class)
    ->name('landing');

// Public booking route: domain-name.coach-name/service-name
Route::get('/{tenantSlug}/{serviceSlug}', Show::class)
    ->name('booking.show');
