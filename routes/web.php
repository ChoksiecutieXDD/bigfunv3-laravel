<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Booking\Inventory;
use App\Livewire\System\SystemSettings;
use App\Livewire\System\DatabaseViewer;

// ==========================================
// PUBLIC ROUTES
// ==========================================
Route::get('/', function () {
    return view('welcome');
});

// ==========================================
// SYSTEM & ADMIN ROUTES
// ==========================================

// Routing directly to Livewire components
Route::get('/settings', SystemSettings::class);
Route::get('/system/db-view', DatabaseViewer::class);
Route::get('/inventory', Inventory::class);
