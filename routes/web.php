<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// Controllers
use App\Http\Controllers\PdfController;

// Google API Clients
use Google\Client as GoogleClient;
use GuzzleHttp\Client as GuzzleClient;

// Livewire Components - Auth & Account
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\PersonalInformation;

// Livewire Components - Bookings
use App\Livewire\BookingOverview as UnifiedBookingOverview;
use App\Livewire\Supervisor\EditBooking;
use App\Livewire\Admin\BookManage as AdminBookManage;

// Livewire Components - Admin Features
use App\Livewire\Admin\AdminDashboard;
use App\Livewire\Admin\Calendar as AdminCalendar;
use App\Livewire\Admin\NewBooking;
use App\Http\Controllers\Admin\BookingApiController;

// Livewire Components - Supervisor Features
use App\Livewire\Supervisor\BookingHistory;
use App\Livewire\Supervisor\Calendar as SupervisorCalendar;
use App\Livewire\Supervisor\FinancialReports;
use App\Livewire\Supervisor\LogisticsInbox;
use App\Livewire\Supervisor\ManageEnquiries;
use App\Livewire\Supervisor\StaffManagement;
use App\Livewire\Supervisor\StaffProfile;

// Livewire Components - Staff Features
use App\Livewire\Staff\StaffDashboard;

// Livewire Components - System & Inventory
use App\Livewire\Booking\Inventory;
use App\Livewire\System\DatabaseViewer;
use App\Livewire\System\SystemSettings;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| GUEST ROUTES (Not Logged In)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', Login::class)->name('login');
    Route::get('/supervisor/login', Login::class)->name('supervisor.login');

    // Password Resets
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES (Must be Logged In)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // --- SESSION & ACCOUNT ---
    Route::post('/logout', function (Request $request) {
        // 1. Capture the user's role BEFORE logging them out
        $role = Auth::check() ? Auth::user()->role : null;

        // 2. Perform the logout and clear the session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 3. Redirect to the correct login page based on the role we saved
        if ($role === 'Supervisor') {
            return redirect('/supervisor/login');
        }

        // Default redirect for Administrator, Staff, etc.
        return redirect('/login');
    })->name('logout');

    Route::get('/profile', PersonalInformation::class)->name('profile');


    // ==========================================
    // ADMIN WORKSPACE
    // ==========================================
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
        Route::get('/calendar', AdminCalendar::class)->name('calendar');

        // Fixed alias here
        Route::get('/bookings', AdminBookManage::class)->name('manages');

        Route::get('/staff', StaffManagement::class)->name('staff');
        Route::get('/reports', FinancialReports::class)->name('reports');
    });


    // ==========================================
    // STAFF WORKSPACE
    // ==========================================
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/dashboard', StaffDashboard::class)->name('dashboard');
        Route::get('/assignments', function () {
            return "Staff Assignments Placeholder";
        })->name('assignments');
        Route::get('/deliveries', function () {
            return "Staff Deliveries Placeholder";
        })->name('deliveries');

        Route::get('/history', BookingHistory::class)->name('history');
    });


    // ==========================================
    // OLD SUPERVISOR ROUTES (Legacy / Active)
    // ==========================================
    Route::get('/supervisor', function () {
        return redirect()->route('supervisor.calendar');
    })->name('supervisor.dashboard');

    Route::get('/supervisor/calendar', SupervisorCalendar::class)->name('supervisor.calendar');
    Route::get('/supervisor/bookings/create', \App\Livewire\Supervisor\NewBooking::class)->name('supervisor.bookings.create');

    Route::get('/history', BookingHistory::class)->name('supervisor.history');
    Route::get('/logistics', LogisticsInbox::class)->name('supervisor.logistics');
    Route::get('/enquiries', ManageEnquiries::class)->name('supervisor.enquiries');
    Route::get('/reports', FinancialReports::class)->name('supervisor.reports');
    Route::get('/staff', StaffManagement::class)->name('supervisor.staff');
    Route::get('/staff/profile/{id}', StaffProfile::class)->name('staff.profile');


    // --- BOOKING MANAGEMENT ---
    Route::prefix('bookings')->name('booking.')->group(function () {
        // ✅ NEW BOOKING ROUTE ADDED
        Route::get('/create', NewBooking::class)->name('create');

        // Used the new Unified BookingOverview
        Route::get('/{id}', UnifiedBookingOverview::class)->name('overview');
        Route::get('/{id}/edit', EditBooking::class)->name('edit');
    });

    // --- PDF GENERATORS ---
    Route::prefix('templates')->name('pdf.')->group(function () {
        Route::get('/invoice_pdf/{id}', [PdfController::class, 'generateInvoice'])->name('invoice');
        Route::get('/purchase_order_pdf/{id}', [PdfController::class, 'generatePurchaseOrder'])->name('po');
        Route::get('/receipt_pdf/{id}', [PdfController::class, 'generateReceipt'])->name('receipt');
        Route::get('/envelope_pdf/{id}', [PdfController::class, 'generateEnvelope'])->name('envelope');
    });

    // --- GOOGLE GMAIL OAUTH ---
    Route::prefix('google')->name('google.')->group(function () {
        Route::get('/setup', function () {
            $client = new GoogleClient();
            $client->setHttpClient(new GuzzleClient(['verify' => false])); // SSL Bypass for local
            $client->setAuthConfig(storage_path('app/google/client_secret.json'));
            $client->setRedirectUri(url('/google/callback'));
            $client->addScope(\Google\Service\Gmail::GMAIL_READONLY);
            $client->addScope(\Google\Service\Gmail::GMAIL_SEND);
            $client->setAccessType('offline');
            $client->setPrompt('select_account consent');
            return redirect($client->createAuthUrl());
        })->name('setup');

        Route::get('/callback', function (Request $request) {
            if (!$request->has('code')) return redirect('/enquiries')->with('error', 'Google Auth Failed');

            $client = new GoogleClient();
            $client->setHttpClient(new GuzzleClient(['verify' => false]));
            $client->setAuthConfig(storage_path('app/google/client_secret.json'));
            $client->setRedirectUri(url('/google/callback'));

            $token = $client->fetchAccessTokenWithAuthCode($request->code);
            if (!array_key_exists('error', $token)) {
                file_put_contents(storage_path('app/google/token.json'), json_encode($token));
                return redirect('/enquiries')->with('success', 'Gmail Connected Successfully!');
            }
            return redirect('/enquiries')->with('error', 'Token Error');
        })->name('callback');
    });

    // --- BOOKING API ---
    Route::get('/api/bookings/check-availability', [BookingApiController::class, 'checkAvailability']);
    Route::post('/api/bookings/handler', [BookingApiController::class, 'handler']);
});

/*
|--------------------------------------------------------------------------
| SYSTEM ROUTES (No Auth Required / External checks)
|--------------------------------------------------------------------------
*/
Route::prefix('system')->name('system.')->group(function () {
    Route::get('/settings', SystemSettings::class)->name('settings');
    Route::get('/db-view', DatabaseViewer::class)->name('db-view');
    Route::get('/inventory', Inventory::class)->name('inventory');
});
