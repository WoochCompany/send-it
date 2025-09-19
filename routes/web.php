<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if(auth()->check()) {
        return redirect()->route('dashboard');
    } else {
        return redirect()->route('login');
    }
})->name('home');

Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Admin routes
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('emails', [App\Http\Controllers\Admin\EmailController::class, 'index'])->name('emails.index');
    Route::get('emails/{message}', [App\Http\Controllers\Admin\EmailController::class, 'show'])->name('emails.show');

    Route::get('providers', [App\Http\Controllers\Admin\ProviderController::class, 'index'])->name('providers.index');
    Route::get('providers/{provider}', [App\Http\Controllers\Admin\ProviderController::class, 'show'])->name('providers.show');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
