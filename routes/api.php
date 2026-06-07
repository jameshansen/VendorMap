<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventTableController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\VenueController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
| These are intentionally open for the first version so you can run the
| designer without setting up auth. Before going to production, wrap the
| write routes in auth:
|
|   Route::middleware('auth:sanctum')->group(function () { ... });
*/

// Venues + their fixed floor features (boundary, doors, power).
Route::get('venues', [VenueController::class, 'index']);
Route::post('venues', [VenueController::class, 'store']);
Route::get('venues/{venue}', [VenueController::class, 'show']);
Route::put('venues/{venue}', [VenueController::class, 'update']);
Route::delete('venues/{venue}', [VenueController::class, 'destroy']);

// Events.
Route::get('events', [EventController::class, 'index']);
Route::post('events', [EventController::class, 'store']);
Route::get('events/{event}', [EventController::class, 'show']);
Route::put('events/{event}', [EventController::class, 'update']);
Route::delete('events/{event}', [EventController::class, 'destroy']);

// Tables belong to an event; the designer saves them all at once.
Route::get('events/{event}/tables', [EventTableController::class, 'index']);
Route::put('events/{event}/tables', [EventTableController::class, 'sync']);

// Vendors.
Route::apiResource('vendors', VendorController::class);
