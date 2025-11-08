<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Booking\Show;
use App\Livewire\Booking\EmployeeShow;
use App\Livewire\Booking\ServiceEmployeesShow;
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

// Public booking routes
// Service employees selection: domain-name/service-slug/employees
Route::get('/{tenantSlug}/{serviceSlug}/employees', ServiceEmployeesShow::class)
    ->where('tenantSlug', '[a-z0-9\-]+')
    ->where('serviceSlug', '[a-z0-9\-]+')
    ->name('booking.service.employees')
    ->middleware('web');

// Employee link: domain-name/employee-slug
Route::get('/{tenantSlug}/{employeeSlug}', EmployeeShow::class)
    ->where('tenantSlug', '[a-z0-9\-]+')
    ->where('employeeSlug', '[a-z0-9\-]+')
    ->name('booking.employee.show')
    ->middleware('web');

// Service booking with optional employee: domain-name/service-slug/employee-slug
Route::get('/{tenantSlug}/{serviceSlug}/{employeeSlug?}', Show::class)
    ->where('tenantSlug', '[a-z0-9\-]+')
    ->where('serviceSlug', '[a-z0-9\-]+')
    ->where('employeeSlug', '(?!employees$)[a-z0-9\-]+')
    ->name('booking.show')
    ->middleware('web');
