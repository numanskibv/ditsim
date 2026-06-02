<?php

use App\Http\Controllers\CableSchedulePdfController;
use App\Http\Controllers\InstallationPlanPdfController;
use App\Http\Controllers\ManualPrintController;
use App\Http\Controllers\PortfolioExportController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('dcim', 'pages::dcim.racks')->name('dcim.racks');
    Route::livewire('dcim/cabling', 'pages::dcim.cabling')->name('dcim.cabling');
    Route::get('dcim/cabling/pdf', CableSchedulePdfController::class)->name('dcim.cabling.pdf');

    Route::livewire('tickets', 'pages::tickets.index')->name('tickets.index');
    Route::livewire('tickets/{ticket}', 'pages::tickets.show')->name('tickets.show');

    Route::livewire('tickets/{ticket}/plan', 'pages::plans.edit')->name('plans.edit');
    Route::get('tickets/{ticket}/plan/pdf', InstallationPlanPdfController::class)->name('plans.pdf');

    Route::livewire('monitoring', 'pages::monitoring.index')->name('monitoring');

    Route::livewire('access', 'pages::access.index')->name('access.index');
    Route::livewire('inspections', 'pages::inspections.index')->name('inspections.index');
    Route::livewire('messages', 'pages::messages.index')->name('messages.index');

    Route::livewire('portfolio', 'pages::portfolio.index')->name('portfolio.index');
    Route::get('portfolio/{assignment}/pdf', PortfolioExportController::class)
        ->whereNumber('assignment')
        ->name('portfolio.pdf');

    Route::livewire('scenarios', 'pages::scenarios.index')
        ->middleware('can:manage-scenarios')
        ->name('scenarios.index');

    Route::livewire('students', 'pages::students.index')
        ->middleware('can:manage-scenarios')
        ->name('students.index');

    Route::middleware('can:manage-scenarios')->group(function () {
        Route::livewire('manuals', 'pages::manuals.index')->name('manuals.index');
        Route::get('manuals/{slug}/print', ManualPrintController::class)->name('manuals.print');
    });
});

require __DIR__.'/settings.php';
