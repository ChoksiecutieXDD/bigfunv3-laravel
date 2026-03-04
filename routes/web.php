<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/settings', function () {
    return view('system.settings');
});

Route::view('/system/db-view', 'system.db_view');
