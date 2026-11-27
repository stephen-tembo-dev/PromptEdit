<?php

use App\Enums\User\PermissionName;
use App\Http\Controllers\Billing\CreditController;
use App\Http\Controllers\Billing\ReconciliationController;
use App\Http\Controllers\Billing\ReportController;
use App\Http\Controllers\Generations\GenerationController;
use App\Http\Controllers\Generations\GenerationQuoteController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\RoleController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->can(PermissionName::ViewDashboard->value)
        ->name('dashboard');

    // Generations
    Route::middleware('can:'.PermissionName::GenerateImages->value)->group(function () {
        Route::get('/generations/create', [GenerationController::class, 'create'])->name('generations.create');
        Route::post('/generations', [GenerationController::class, 'store'])->name('generations.store');
        Route::post('/generations/quote', GenerationQuoteController::class)->name('generations.quote');
    });

    Route::middleware('can:'.PermissionName::ViewHistory->value)->group(function () {
        Route::get('/generations', [GenerationController::class, 'index'])->name('generations.index');
        Route::get('/generations/{generation}', [GenerationController::class, 'show'])->name('generations.show');
        Route::get('/generations/{generation}/download', [GenerationController::class, 'download'])->name('generations.download');
    });

    // Billing
    Route::middleware('can:'.PermissionName::ManageCredits->value)->group(function () {
        Route::get('/credits', [CreditController::class, 'index'])->name('credits.index');
        Route::post('/credits', [CreditController::class, 'store'])->name('credits.store');
    });

    Route::get('/reports', [ReportController::class, 'index'])
        ->can(PermissionName::ViewReports->value)
        ->name('reports.index');

    // Admin
    Route::get('/admin/report', ReconciliationController::class)
        ->can(PermissionName::ViewAdminReport->value)
        ->name('admin.report');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)
            ->only(['index', 'create', 'store', 'edit', 'update'])
            ->middleware('can:'.PermissionName::ManageUsers->value);

        Route::resource('roles', RoleController::class)
            ->except('show')
            ->middleware('can:'.PermissionName::ManageRoles->value);
    });
});
