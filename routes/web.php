<?php

use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ConditionsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DesignerController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PresetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Public booking site
// ---------------------------------------------------------------------------
Route::get('/', [EventController::class, 'index'])->name('home');

// Vendor authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');

    // Google OAuth (Socialite)
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Profile completion for new Google accounts (no vendor profile yet)
    Route::get('/register/complete', [GoogleController::class, 'showComplete'])->name('register.complete');
    Route::post('/register/complete', [GoogleController::class, 'storeComplete'])->name('register.complete.store');

    // Vendor profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Booking: viewing the floor plan is public; claiming a table needs approval.
Route::get('/events/{event}', [BookingController::class, 'show'])->name('events.show');
Route::middleware('approved.vendor')->group(function () {
    Route::post('/events/{event}/book', [BookingController::class, 'store'])->name('events.book');
    Route::delete('/events/{event}/bookings/{table}', [BookingController::class, 'destroy'])->name('events.unbook');
});

// ---------------------------------------------------------------------------
// Admin panel (separate login; credentials live in config.php)
// ---------------------------------------------------------------------------
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminSessionController::class, 'show'])->name('login');
    Route::post('/login', [AdminSessionController::class, 'login'])->name('login.post')->middleware('throttle:10,1');
    Route::post('/logout', [AdminSessionController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Event management (full CRUD)
        Route::resource('events', AdminEventController::class)->except(['show']);

        // The floor-plan designer (moved here from the public site)
        Route::get('events/{event}/designer', [DesignerController::class, 'show'])->name('designer.show');
        Route::post('events/{event}/layout', [DesignerController::class, 'save'])->name('designer.save');

        // Designer-only tools: venue preview / create / duplicate / rename
        Route::get('events/{event}/venue/{venue}', [VenueController::class, 'preview'])->name('venue.preview');
        Route::post('events/{event}/venue/new', [VenueController::class, 'create'])->name('venue.create');
        Route::post('events/{event}/venue/duplicate', [VenueController::class, 'duplicate'])->name('venue.duplicate');
        Route::post('events/{event}/venue/{venue}/rename', [VenueController::class, 'rename'])->name('venue.rename');

        // Object presets used by the designer palette
        Route::get('presets', [PresetController::class, 'index'])->name('presets.index');
        Route::post('presets', [PresetController::class, 'store'])->name('presets.store');
        Route::delete('presets/{preset}', [PresetController::class, 'destroy'])->name('presets.destroy');

        // Bookings + offline payment tracking
        Route::get('events/{event}/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::post('events/{event}/bookings/{table}/confirm', [AdminBookingController::class, 'confirm'])->name('bookings.confirm');
        Route::post('events/{event}/bookings/{table}/paid', [AdminBookingController::class, 'markPaid'])->name('bookings.paid');
        Route::post('events/{event}/bookings/{table}/unpaid', [AdminBookingController::class, 'markUnpaid'])->name('bookings.unpaid');

        // Vendor product categories (suggestion list)
        Route::get('categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        // Global vendor conditions / liability / rules document
        Route::get('conditions', [ConditionsController::class, 'edit'])->name('conditions.edit');
        Route::put('conditions', [ConditionsController::class, 'update'])->name('conditions.update');

        // Vendor approvals
        Route::get('vendors', [AdminVendorController::class, 'index'])->name('vendors.index');
        Route::post('vendors/{vendor}/approve', [AdminVendorController::class, 'approve'])->name('vendors.approve');
        Route::post('vendors/{vendor}/reject', [AdminVendorController::class, 'reject'])->name('vendors.reject');
    });
});
