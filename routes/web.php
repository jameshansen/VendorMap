<?php

use App\Http\Controllers\DesignerController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PresetController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('events.index'));

Route::get('/events', [EventController::class, 'index'])->name('events.index');

Route::get('/events/{event}/designer', [DesignerController::class, 'show'])->name('designer.show');
Route::post('/events/{event}/layout', [DesignerController::class, 'save'])->name('designer.save');

// Venue preview / creation / duplication. None of these commit the venue to the
// event; that happens only when the designer is saved (with the chosen venue_id).
Route::get('/events/{event}/venue/{venue}', [VenueController::class, 'preview'])->name('venue.preview');
Route::post('/events/{event}/venue/new', [VenueController::class, 'create'])->name('venue.create');
Route::post('/events/{event}/venue/duplicate', [VenueController::class, 'duplicate'])->name('venue.duplicate');

// Globally shared object presets (used by the designer palette)
Route::get('/presets', [PresetController::class, 'index'])->name('presets.index');
Route::post('/presets', [PresetController::class, 'store'])->name('presets.store');
Route::delete('/presets/{preset}', [PresetController::class, 'destroy'])->name('presets.destroy');
