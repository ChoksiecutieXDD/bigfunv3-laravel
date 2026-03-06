<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Booking\Inventory;
use App\Livewire\System\SystemSettings;
use App\Livewire\System\DatabaseViewer;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\SupervisorLogin;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Supervisor\Calendar;

// ==========================================
// PUBLIC ROUTES
// ==========================================
Route::get('/', function () {
    return view('welcome');
});

// ==========================================
// SYSTEM CONFIGURATION
// ==========================================

// Routing directly to Livewire components
Route::get('/settings', SystemSettings::class);
Route::get('/system/db-view', DatabaseViewer::class);
Route::get('/inventory', Inventory::class);

// ==========================================
// AUTHENTICATION ROUTES
// ==========================================
Route::get('/login', Login::class)->middleware('guest')->name('login');
Route::get('/supervisor/login', SupervisorLogin::class)->middleware('guest')->name('supervisor.login');
Route::get('/forgot-password', ForgotPassword::class)->middleware('guest')->name('password.request');
Route::get('/reset-password/{token}', ResetPassword::class)->middleware('guest')->name('password.reset');

// ==========================================
// SUPERVISOR ROUTES
// ==========================================
Route::get('/supervisor', SupervisorLogin::class)->middleware('auth')->name('supervisor');
Route::get('/supervisor/calendar', Calendar::class)->middleware('auth')->name('supervisor.calendar');
