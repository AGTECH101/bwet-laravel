<?php

use App\Http\Controllers\General\DashboardController;
use App\Http\Controllers\General\SectorSelectionController;
use App\Http\Controllers\General\SectorController;
use App\Http\Controllers\General\PriceCalculatorController;
use App\Http\Controllers\General\NotificationController;
use App\Http\Controllers\General\ObservationController;
use App\Http\Controllers\General\HistoryQueryController;
use App\Http\Controllers\General\ExportController;
use App\Http\Controllers\General\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Landing page (public)
Route::view('/', 'welcome')->name('home');

// Authentication routes are automatically registered by Laravel Fortify
// But we need to override the post-login redirect.
// We'll set HOME in RouteServiceProvider to '/sectors' (see below)

// Sector selection (authenticated)
Route::middleware(['auth'])->group(function () {
    Route::get('/sectors', [SectorSelectionController::class, 'index'])->name('sectors.index');
    Route::post('/sectors/select', [SectorSelectionController::class, 'select'])->name('sectors.select');
});

// Authenticated routes (require sector selection)
Route::middleware(['auth', 'verified', 'sector.selected'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Sector picker (optional)
    Route::get('/sectors/list', [SectorController::class, 'index'])->name('sectors.list');

    // ... all other authenticated routes
    // Price Calculator
    Route::get('/price-calculator', [PriceCalculatorController::class, 'index'])->name('price-calculator.index');
    Route::post('/price-calculator', [PriceCalculatorController::class, 'calculate'])->name('price-calculator.calculate');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');

    // Observations (general)
    Route::resource('/observations', ObservationController::class)->except(['edit', 'update', 'destroy']);
    Route::get('/observations/{observation}/review', [ObservationController::class, 'reviewForm'])->name('observations.review.form');
    Route::post('/observations/{observation}/review', [ObservationController::class, 'review'])->name('observations.review');

    // History Queries
    Route::get('/history', [HistoryQueryController::class, 'index'])->name('history.index');
    Route::post('/history/query', [HistoryQueryController::class, 'execute'])->name('history.execute');
    Route::get('/history/{query}', [HistoryQueryController::class, 'show'])->name('history.show');

    // Export
    Route::get('/export', [ExportController::class, 'index'])->name('export.index');
    Route::post('/export', [ExportController::class, 'export'])->name('export.run');

    // Admin user management
    Route::middleware('can:manage-users')->group(function () {
        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::post('/admin/users/{user}/approve', [AdminUserController::class, 'approve'])->name('admin.users.approve');
        Route::post('/admin/users/{user}/deactivate', [AdminUserController::class, 'deactivate'])->name('admin.users.deactivate');
    });

    // System variables (admin only)
    Route::middleware('can:manage-system-variables')->prefix('system')->name('system.')->group(function () {
        Route::get('/variables', [\App\Http\Controllers\General\SystemVariableController::class, 'index'])->name('variables.index');
        Route::get('/variables/{variable}/edit', [\App\Http\Controllers\General\SystemVariableController::class, 'edit'])->name('variables.edit');
        Route::put('/variables/{variable}', [\App\Http\Controllers\General\SystemVariableController::class, 'update'])->name('variables.update');
    });

    // Market prices (admin only)
    Route::middleware('can:manage-market-prices')->prefix('system')->name('system.')->group(function () {
        Route::get('/market-prices', [\App\Http\Controllers\General\MarketPriceController::class, 'index'])->name('market-prices.index');
        Route::post('/market-prices', [\App\Http\Controllers\General\MarketPriceController::class, 'store'])->name('market-prices.store');
    });

    // Profile
    Route::get('/profile', [\App\Http\Controllers\General\ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [\App\Http\Controllers\General\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\General\ProfileController::class, 'update'])->name('profile.update');

    // Quick refresh endpoint
    // Route::post('/quick-refresh', [DashboardController::class, 'quickRefresh'])->name('quick-refresh');

    // Load all sector-specific routes (they will be prefixed with the sector slug)
    require base_path('routes/sectors/poultry.php');
    // Future sectors: fishery, goat, etc. will be loaded similarly.
});

// If you want to redirect /home to landing or dashboard, add:
Route::get('/home', function () {
    return redirect()->route(auth()->check() ? 'sectors.index' : 'home');
});