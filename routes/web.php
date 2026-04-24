<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------

// Controllers
use App\Http\Controllers\PdfController;
use App\Http\Controllers\Admin\BookingApiController;

// Google API Clients
use Google\Client as GoogleClient;
use GuzzleHttp\Client as GuzzleClient;

// Livewire Components - Auth & Account
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\PersonalInformation;

// Livewire Components - Bookings
use App\Livewire\Booking\Inventory;
use App\Livewire\Admin\BookingOverview as AdminOverview;
use App\Livewire\Staff\BookingOverview as StaffOverview;

// Livewire Components - Admin
use App\Livewire\Admin\AdminDashboard;
use App\Livewire\Admin\Calendar as AdminCalendar;
use App\Livewire\Admin\BookManage as AdminBookManage;
use App\Livewire\Admin\NewBooking as AdminNewBooking;
use App\Livewire\Admin\StaffManagement as AdminStaffManagement;
use App\Livewire\Admin\StaffProfile as AdminStaffProfile;
use App\Livewire\Admin\FinancialReports as AdminFinancialReports;

// Livewire Components - Supervisor
use App\Livewire\Supervisor\BookingHistory;
use App\Livewire\Supervisor\Calendar as SupervisorCalendar;
use App\Livewire\Supervisor\EditBooking;
use App\Livewire\Supervisor\FinancialReports;
use App\Livewire\Supervisor\LogisticsInbox;
use App\Livewire\Supervisor\ManageEnquiries;
use App\Livewire\Supervisor\NewBooking as SupervisorNewBooking;
use App\Livewire\Supervisor\StaffManagement;
use App\Livewire\Supervisor\StaffProfile;

// Livewire Components - Staff
use App\Livewire\Staff\StaffDashboard;

// Livewire Components - System
use App\Livewire\System\DatabaseViewer;
use App\Livewire\System\SystemSettings;


/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

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

    // ==========================================
    // SESSION & ACCOUNT
    // ==========================================
    Route::post('/logout', function (Request $request) {
        $role = Auth::check() ? Auth::user()->role : null;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($role === 'Supervisor') {
            return redirect('/supervisor/login')->with('logged_out', true);
        }

        return redirect('/login')->with('logged_out', true);
    })->name('logout');

    Route::get('/profile', PersonalInformation::class)->name('profile');

    // ==========================================
    // ADMIN WORKSPACE
    // ==========================================
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
        Route::get('/calendar', AdminCalendar::class)->name('calendar');
        Route::get('/bookings', AdminBookManage::class)->name('manages'); // Retained your alias
        Route::get('/staff', AdminStaffManagement::class)->name('staff');
        Route::get('/staff/profile/{id}', AdminStaffProfile::class)->name('staff.profile');
        Route::get('/reports', AdminFinancialReports::class)->name('reports');
        
        // Admin Bookings
        Route::get('/bookings/create', AdminNewBooking::class)->name('bookings.create');
        Route::get('/bookings/overview/{id}', AdminOverview::class)->name('bookings.overview');
    });

    // ==========================================
    // SUPERVISOR WORKSPACE
    // ==========================================
    Route::prefix('supervisor')->name('supervisor.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('supervisor.calendar');
        })->name('dashboard');

        Route::get('/calendar', SupervisorCalendar::class)->name('calendar');
        Route::get('/bookings/create', SupervisorNewBooking::class)->name('bookings.create');
        Route::get('/bookings/{id}', \App\Livewire\Supervisor\BookingOverview::class)->name('bookings.overview');
        Route::get('/bookings/{id}/edit', \App\Livewire\Supervisor\EditBooking::class)->name('bookings.edit');
        Route::get('/customer/{id}', \App\Livewire\Supervisor\CustomerProfile::class)->name('customer.profile');
        Route::get('/history', BookingHistory::class)->name('history');
        Route::get('/logistics', LogisticsInbox::class)->name('logistics');
        Route::get('/enquiries', ManageEnquiries::class)->name('enquiries');
        Route::get('/reports', FinancialReports::class)->name('reports');
        Route::get('/staff', StaffManagement::class)->name('staff');
        Route::get('/staff/profile/{id}', StaffProfile::class)->name('staff.profile');
    });

    // ==========================================
    // STAFF WORKSPACE
    // ==========================================
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/dashboard', StaffDashboard::class)->name('dashboard');
        Route::get('/assignments', \App\Livewire\Staff\StaffAssignments::class)->name('assignments');
        Route::get('/deliveries', \App\Livewire\Staff\StaffDeliveries::class)->name('deliveries');
        Route::get('/history', \App\Livewire\Staff\StaffHistory::class)->name('history');
        Route::get('/bookings/overview/{id}', StaffOverview::class)->name('bookings.overview');
    });

    // ==========================================
    // INVENTORY & SYSTEM
    // ==========================================
    Route::get('/inventory', Inventory::class)->name('inventory');

    // ==========================================
    // PDF GENERATORS
    // ==========================================
    Route::prefix('templates')->name('pdf.')->group(function () {
        Route::get('/invoice_pdf/{id}', [PdfController::class, 'generateInvoice'])->name('invoice');
        Route::get('/purchase_order_pdf/{id}', [PdfController::class, 'generatePurchaseOrder'])->name('po');
        Route::get('/receipt_pdf/{id}', [PdfController::class, 'generateReceipt'])->name('receipt');

        Route::get('/debt_pdf/{id}', [PdfController::class, 'generateDebt'])->name('debt');
        Route::get('/delivery_receipt_pdf/{id}', [PdfController::class, 'generateDeliveryReceipt'])->name('delivery_receipt');
    });

    // ==========================================
    // GOOGLE GMAIL OAUTH
    // ==========================================
    Route::prefix('google')->name('google.')->group(function () {
        Route::get('/fetch-emails', [App\Http\Controllers\GmailImportController::class, 'fetchEmails'])->name('fetch-emails');
        
        Route::get('/setup', function () {
            $client = new GoogleClient();
            $client->setHttpClient(new GuzzleClient(['verify' => false]));
            $client->setAuthConfig(storage_path('app/google/client_secret.json'));
            $client->setRedirectUri(url('/google/callback'));
            $client->addScope(\Google\Service\Gmail::GMAIL_READONLY);
            $client->addScope(\Google\Service\Gmail::GMAIL_SEND);
            $client->setAccessType('offline');
            $client->setPrompt('select_account consent');
            return redirect($client->createAuthUrl());
        })->name('setup');

        Route::get('/callback', function (Request $request) {
            if (!$request->has('code')) return redirect('/supervisor/enquiries')->with('error', 'Google Auth Failed');

            $client = new GoogleClient();
            $client->setHttpClient(new GuzzleClient(['verify' => false]));
            $client->setAuthConfig(storage_path('app/google/client_secret.json'));
            $client->setRedirectUri(url('/google/callback'));

            $token = $client->fetchAccessTokenWithAuthCode($request->code);
            if (!array_key_exists('error', $token)) {
                file_put_contents(storage_path('app/google/token.json'), json_encode($token));
                return redirect('/supervisor/enquiries')->with('success', 'Gmail Connected Successfully!');
            }
            return redirect('/supervisor/enquiries')->with('error', 'Token Error');
        })->name('callback');
    });

    // ==========================================
    // INTERNAL API HANDLERS
    // ==========================================
    Route::prefix('api/bookings')->group(function () {
        Route::get('/check-availability', [BookingApiController::class, 'checkAvailability']);
        Route::post('/handler', [BookingApiController::class, 'handler']);
    });
});

/*
|--------------------------------------------------------------------------
| SYSTEM & INVENTORY ROUTES (No Auth Required Currently)
|--------------------------------------------------------------------------
| Note: You might want to move these inside the 'auth' middleware
| in the future if they contain sensitive system data!
*/
Route::prefix('system')->name('system.')->group(function () {
    Route::get('/settings', SystemSettings::class)->name('settings');
    Route::get('/db-view', DatabaseViewer::class)->name('db-view');
    Route::get('/inventory', Inventory::class)->name('inventory');
});
