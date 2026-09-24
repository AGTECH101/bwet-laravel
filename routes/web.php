<?php

use App\Http\Controllers\General\DashboardController;
use App\Http\Controllers\General\SectorSelectionController;
use App\Http\Controllers\General\SectorController;
use App\Http\Controllers\General\NotificationController;
use App\Http\Controllers\General\ObservationController;
use App\Http\Controllers\General\HistoryQueryController;
use App\Http\Controllers\General\ExportController;
use App\Http\Controllers\General\AdminUserController;
use App\Http\Controllers\General\BatchTransferAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Landing page (public)
Route::view('/', 'welcome')->name('home');

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

    // Load all sector-specific routes
    require base_path('routes/sectors/poultry.php');
});

// ============================================================
// CONTROL PANEL (Admin Only) - outside sector.selected middleware
// ============================================================
Route::middleware(['auth', 'can:admin'])->prefix('control-panel')->name('control-panel.')->group(function () {
    Route::get('/', [App\Http\Controllers\General\ControlPanelController::class, 'index'])->name('index');
    Route::get('/record', [App\Http\Controllers\General\ControlPanelController::class, 'getRecord'])->name('get-record');
    Route::put('/record', [App\Http\Controllers\General\ControlPanelController::class, 'updateRecord'])->name('update-record');
    Route::delete('/record', [App\Http\Controllers\General\ControlPanelController::class, 'deleteRecord'])->name('delete-record');
});

// ============================================================
// BATCH TRANSFER ADMIN EDIT (Admin Only)
// Accessible from the History tab regardless of the currently
// selected sector so the admin can edit a transfer from anywhere.
// ============================================================
Route::middleware(['auth', 'can:admin'])->prefix('admin/transfers')->name('admin.transfers.')->group(function () {
    Route::get('{transfer}/edit', [BatchTransferAdminController::class, 'edit'])->name('edit');
    Route::put('{transfer}', [BatchTransferAdminController::class, 'update'])->name('update');
});

// ============================================================
// RECALCULATION ROUTE (Admin Only)
// Use this to force-recalculate all batches from raw records.
// Visit once in browser: /recalculate-batches
// ============================================================
Route::middleware(['auth', 'can:admin'])->get('/recalculate-batches', function () {
    try {
        $count = \App\Services\Poultry\BatchRecalculationService::recalculateAllBatches();
        return "<pre>Successfully recalculated {$count} batches.</pre>";
    } catch (\Exception $e) {
        return "<pre>Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
    }
})->name('recalculate-batches');

// Home redirect
Route::get('/home', function () {
    return redirect()->route(auth()->check() ? 'sectors.index' : 'home');
});

// To run migrations online
Route::get('/run-migrations', function () {
    if (!auth()->check() || auth()->user()->role !== 'admin') {
        abort(403, 'Unauthorized.');
    }

    try {
        \Artisan::call('migrate', ['--force' => true]);
        return '<pre>' . \Artisan::output() . '</pre>';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});