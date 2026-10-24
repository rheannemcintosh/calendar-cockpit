<?php

use App\Http\Controllers\CalendarItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubCategoryController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('calendar-items', CalendarItemController::class)->only(['index', 'create', 'store']);

    Route::resource('categories', CategoryController::class)->only(['index', 'create', 'store']);

    Route::resource('sub-categories', SubCategoryController::class)->only(['create', 'store']);
});

require __DIR__.'/settings.php';
