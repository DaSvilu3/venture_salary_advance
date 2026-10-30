<?php

use App\Http\Controllers\Employer\AgreementController;
use App\Http\Controllers\Employer\DashboardController;
use App\Http\Controllers\Employer\DocumentController;
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

Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
Route::get('documents/{document}/download', [DocumentController::class, 'download'])->whereNumber('document')->name('documents.download');
Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->whereNumber('document')->name('documents.destroy');

Route::get('agreement', [AgreementController::class, 'show'])->name('agreement.show');
Route::post('agreement/accept', [AgreementController::class, 'accept'])->name('agreement.accept');
Route::post('agreement/reject', [AgreementController::class, 'reject'])->name('agreement.reject');
Route::post('agreement/signed', [AgreementController::class, 'uploadSigned'])->name('agreement.signed.store');
Route::get('agreement/signed', [AgreementController::class, 'downloadSigned'])->name('agreement.signed.download');

Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
