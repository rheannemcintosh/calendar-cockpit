<?php

use App\Http\Controllers\CalendarItemController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('events', CalendarItemController::class)->only(['index']);
});

require __DIR__.'/settings.php';
