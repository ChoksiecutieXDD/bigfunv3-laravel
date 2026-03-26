<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// Controllers
use App\Http\Controllers\PdfController;

// Livewire Components - Auth
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;

// Livewire Components - Supervisor
use App\Livewire\Supervisor\Calendar;
use App\Livewire\Supervisor\BookingOverview;
use App\Livewire\Supervisor\EditBooking; // <-- Added EditBooking

// Livewire Components - System & Inventory
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
// SYSTEM CONFIGURATION
// Note: You might want to move these inside the 'auth' middleware 
// block below so random visitors can't view your database!
// ==========================================
Route::get('/settings', SystemSettings::class);
Route::get('/system/db-view', DatabaseViewer::class);
Route::get('/inventory', Inventory::class);

// ==========================================
// GUEST ROUTES (Only for users NOT logged in)
// ==========================================
Route::middleware('guest')->group(function () {
    // Standard & Supervisor Login (Uses the exact same component)
    Route::get('/login', Login::class)->name('login');
    Route::get('/supervisor/login', Login::class)->name('supervisor.login');

    // Password Resets
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

// ==========================================
// AUTHENTICATED ROUTES (Must be logged in)
// ==========================================
Route::middleware('auth')->group(function () {

    // --- SESSION DESTRUCTION ---
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/supervisor/login');
    })->name('logout');

    // --- SUPERVISOR DASHBOARD & CALENDAR ---
    Route::get('/supervisor', function () {
        return redirect()->route('supervisor.calendar');
    })->name('supervisor.dashboard');

    Route::get('/supervisor/calendar', Calendar::class)->name('supervisor.calendar');


    // --- BOOKING MANAGEMENT ---
    // View booking overview
    Route::get('/bookings/{id}', BookingOverview::class)->name('booking.overview');
    // Edit booking (Connects to our new component!)
    Route::get('/bookings/{id}/edit', EditBooking::class)->name('booking.edit');


    // --- PDF GENERATORS ---
    // Grouped with a prefix to make the code cleaner
    Route::prefix('templates')->name('pdf.')->group(function () {
        Route::get('/invoice_pdf/{id}', [PdfController::class, 'generateInvoice'])->name('invoice');
        Route::get('/purchase_order_pdf/{id}', [PdfController::class, 'generatePurchaseOrder'])->name('po');
        Route::get('/receipt_pdf/{id}', [PdfController::class, 'generateReceipt'])->name('receipt');
        Route::get('/envelope_pdf/{id}', [PdfController::class, 'generateEnvelope'])->name('envelope');
    });


    // --- PLACEHOLDERS FOR FUTURE FEATURES ---
    // Uncomment and attach your Livewire components when you build them!
    // Route::get('/bookings/create', CreateBooking::class)->name('bookings.create');
    // Route::get('/history', BookingHistory::class);
    // Route::get('/logistics', LogisticsInbox::class);
    // Route::get('/enquiries', ManageEnquiries::class);
    // Route::get('/staff', ManageStaff::class);
    // Route::get('/reports', FinancialReports::class);
    // Route::get('/profile', UserProfile::class);

});
