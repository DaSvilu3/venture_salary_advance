<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::put('locale', LocaleController::class)->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:employee'])
    ->prefix('app')
    ->name('app.')
    ->group(__DIR__.'/employee.php');

Route::middleware(['auth', 'verified', 'role:employer_admin'])
    ->prefix('employer')
    ->name('employer.')
    ->group(__DIR__.'/employer.php');

Route::middleware(['auth', 'verified', 'role:platform_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(__DIR__.'/admin.php');

Route::middleware(['auth', 'verified', 'role:partner_viewer'])
    ->prefix('partner')
    ->name('partner.')
    ->group(__DIR__.'/partner.php');

require __DIR__.'/settings.php';
