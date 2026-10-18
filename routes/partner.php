<?php

use App\Http\Controllers\Partner\AdvanceController;
use App\Http\Controllers\Partner\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('advances', [AdvanceController::class, 'index'])->name('advances.index');
Route::get('advances/export', [AdvanceController::class, 'export'])->name('advances.export');
