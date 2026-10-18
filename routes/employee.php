<?php

use App\Http\Controllers\Employee\AdvanceController;
use App\Http\Controllers\Employee\HomeController;
use App\Http\Controllers\Employee\OnboardingController;
use App\Http\Controllers\Employee\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding');
Route::put('onboarding/bank-account', [OnboardingController::class, 'updateBankAccount'])->name('onboarding.bank-account');
Route::post('onboarding/mandate', [OnboardingController::class, 'storeMandate'])
    ->middleware('throttle:6,1')
    ->name('onboarding.mandate');

Route::get('advances', [AdvanceController::class, 'index'])->name('advances.index');
Route::get('advances/create', [AdvanceController::class, 'create'])->name('advances.create');
Route::post('advances', [AdvanceController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('advances.store');
Route::get('advances/{reference}', [AdvanceController::class, 'show'])->name('advances.show');

Route::get('profile', ProfileController::class)->name('profile');
