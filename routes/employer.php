<?php

use App\Http\Controllers\Employer\DashboardController;
use App\Http\Controllers\Employer\EmployeeController;
use App\Http\Controllers\Employer\EnrolmentController;
use App\Http\Controllers\Employer\InvitationController;
use App\Http\Controllers\Employer\RosterImportController;
use App\Http\Controllers\Employer\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
Route::post('employees/import', [RosterImportController::class, 'store'])->name('employees.import');
Route::get('employees/{employee}', [EmployeeController::class, 'show'])->whereNumber('employee')->name('employees.show');
Route::put('employees/{employee}', [EmployeeController::class, 'update'])->whereNumber('employee')->name('employees.update');
Route::post('employees/{employee}/invitation', [InvitationController::class, 'store'])->whereNumber('employee')->name('employees.invitation');
Route::post('employees/{employee}/approval', [EnrolmentController::class, 'approve'])->whereNumber('employee')->name('employees.approve');
Route::post('employees/{employee}/rejection', [EnrolmentController::class, 'reject'])->whereNumber('employee')->name('employees.reject');

Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
