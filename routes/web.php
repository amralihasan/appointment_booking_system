<?php

use App\Livewire\Booking\Show;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public booking route: domain-name.coach-name/service-name
Route::get('/{tenantSlug}/{serviceSlug}', Show::class)
    ->name('booking.show');
