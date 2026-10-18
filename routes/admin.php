<?php

use App\Http\Controllers\Admin\AdvanceActionController;
use App\Http\Controllers\Admin\AdvanceController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisclosureController;
use App\Http\Controllers\Admin\EmployerController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\ProgramTermsController;
use App\Http\Controllers\Admin\SimulationController;
use App\Http\Controllers\Admin\StaffInvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('employers', [EmployerController::class, 'index'])->name('employers.index');
Route::post('employers', [EmployerController::class, 'store'])->name('employers.store');
Route::get('employers/{employer}', [EmployerController::class, 'show'])->name('employers.show');
Route::put('employers/{employer}', [EmployerController::class, 'update'])->name('employers.update');
Route::post('employers/{employer}/admins', [StaffInvitationController::class, 'storeEmployerAdmin'])->name('employers.admins.store');
Route::put('employers/{employer}/terms', [ProgramTermsController::class, 'updateForEmployer'])->name('employers.terms.update');
Route::delete('employers/{employer}/terms', [ProgramTermsController::class, 'destroyForEmployer'])->name('employers.terms.destroy');

Route::get('partners', [PartnerController::class, 'index'])->name('partners.index');
Route::post('partners', [PartnerController::class, 'store'])->name('partners.store');
Route::put('partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
Route::post('partners/{partner}/viewers', [StaffInvitationController::class, 'storePartnerViewer'])->name('partners.viewers.store');

Route::get('terms', [ProgramTermsController::class, 'edit'])->name('terms.edit');
Route::put('terms', [ProgramTermsController::class, 'update'])->name('terms.update');

Route::get('disclosures', [DisclosureController::class, 'index'])->name('disclosures.index');
Route::post('disclosures', [DisclosureController::class, 'store'])->name('disclosures.store');
Route::post('disclosures/{disclosure}/publish', [DisclosureController::class, 'publish'])->name('disclosures.publish');

Route::get('advances', [AdvanceController::class, 'index'])->name('advances.index');
Route::get('advances/{advance:reference}', [AdvanceController::class, 'show'])->name('advances.show');
Route::post('advances/{advance:reference}/cancel', [AdvanceActionController::class, 'cancel'])->name('advances.cancel');
Route::post('advances/{advance:reference}/write-off', [AdvanceActionController::class, 'writeOff'])->name('advances.write-off');
Route::post('advances/{advance:reference}/collect', [AdvanceActionController::class, 'retryCollection'])->name('advances.collect');

Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

Route::get('simulation', [SimulationController::class, 'show'])->name('simulation.show');
Route::put('simulation/clock', [SimulationController::class, 'updateClock'])->name('simulation.clock.update');
Route::delete('simulation/clock', [SimulationController::class, 'resetClock'])->name('simulation.clock.reset');
Route::post('simulation/collections', [SimulationController::class, 'runCollections'])->name('simulation.collections.run');
Route::post('simulation/failures', [SimulationController::class, 'storeFailure'])->name('simulation.failures.store');
Route::delete('simulation/failures', [SimulationController::class, 'clearFailures'])->name('simulation.failures.clear');
