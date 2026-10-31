<?php

use App\Http\Controllers\CalendarItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LocationSearchController;
use App\Http\Controllers\SubCategoryController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('calendar-items', CalendarItemController::class)->only(['index', 'create', 'store']);

    Route::get('locations/search', LocationSearchController::class)
        ->middleware('throttle:60,1')
        ->name('locations.search');

    Route::resource('categories', CategoryController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->middlewareFor(['edit', 'update'], 'can:update,category')
        ->middlewareFor('destroy', 'can:delete,category');

    Route::resource('sub-categories', SubCategoryController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->middlewareFor(['edit', 'update'], 'can:update,sub_category')
        ->middlewareFor('destroy', 'can:delete,sub_category');
});

require __DIR__.'/settings.php';
