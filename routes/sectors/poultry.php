<?php

use App\Http\Controllers\Poultry\BatchController;
use App\Http\Controllers\Poultry\FlockRecordController;
use App\Http\Controllers\Poultry\WeightRecordController;
use App\Http\Controllers\Poultry\FeedRecordController;
use App\Http\Controllers\Poultry\InventoryController;
use App\Http\Controllers\Poultry\ExpenseController;
use App\Http\Controllers\Poultry\InventoryConsumptionController;
use App\Http\Controllers\Poultry\AnalyticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Poultry Sector Routes
|--------------------------------------------------------------------------
*/

Route::prefix('poultry')->name('poultry.')->middleware(['auth', 'verified'])->group(function () {

    // Batches
    Route::resource('batches', BatchController::class);
    Route::post('batches/{batch}/export', [BatchController::class, 'export'])->name('batches.export');
    Route::post('batches/{batch}/manual-mode', [BatchController::class, 'toggleManualMode'])->name('batches.manual-mode');

    // Flock Records
    Route::resource('flock-records', FlockRecordController::class)->except(['index']);
    Route::get('batches/{batch}/flock-records', [FlockRecordController::class, 'index'])->name('batches.flock-records');

    // Weight Records
    Route::resource('weight-records', WeightRecordController::class)->except(['index']);
    Route::get('batches/{batch}/weight-records', [WeightRecordController::class, 'index'])->name('batches.weight-records');

    // Feed Records
    Route::resource('feed-records', FeedRecordController::class)->except(['index']);
    Route::get('batches/{batch}/feed-records', [FeedRecordController::class, 'index'])->name('batches.feed-records');

    // Inventory Items
    Route::resource('inventory', InventoryController::class);
    Route::post('inventory/{item}/kill', [InventoryController::class, 'kill'])->name('inventory.kill');
    Route::post('inventory/{item}/recalculate-costs', [InventoryController::class, 'recalculateCosts'])->name('inventory.recalculate');

    // Inventory Consumption
    Route::resource('inventory-consumptions', InventoryConsumptionController::class)->except(['index', 'show']);
    Route::get('inventory-consumptions/create', [InventoryConsumptionController::class, 'create'])->name('inventory-consumptions.create');

    // Expenses
    Route::resource('expenses', ExpenseController::class)->except(['index']);
    Route::get('batches/{batch}/expenses', [ExpenseController::class, 'index'])->name('batches.expenses');

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('global', [AnalyticsController::class, 'global'])->name('global');
        Route::get('batches/{batch}/charts', [AnalyticsController::class, 'charts'])->name('charts');
        Route::get('batches/{batch}/realtime', [AnalyticsController::class, 'realtime'])->name('realtime');
    });

    // Form Hub (convenience)
    Route::get('forms/hub', [\App\Http\Controllers\Poultry\FormHubController::class, 'index'])->name('forms.hub');

    // Individual form routes (you might redirect to resources or use specific controllers)
    Route::get('forms/flock-record/create', [FlockRecordController::class, 'create'])->name('forms.flock-record.create');
    Route::post('forms/flock-record', [FlockRecordController::class, 'store'])->name('forms.flock-record.store');

    Route::get('forms/weight-record/create', [WeightRecordController::class, 'create'])->name('forms.weight-record.create');
    Route::post('forms/weight-record', [WeightRecordController::class, 'store'])->name('forms.weight-record.store');

    Route::get('forms/feed-record/create', [FeedRecordController::class, 'create'])->name('forms.feed-record.create');
    Route::post('forms/feed-record', [FeedRecordController::class, 'store'])->name('forms.feed-record.store');

    Route::get('forms/expense/create', [ExpenseController::class, 'create'])->name('forms.expense.create');
    Route::post('forms/expense', [ExpenseController::class, 'store'])->name('forms.expense.store');

    Route::get('forms/inventory-consumption/create', [InventoryConsumptionController::class, 'create'])->name('forms.inventory-consumption.create');
    Route::post('forms/inventory-consumption', [InventoryConsumptionController::class, 'store'])->name('forms.inventory-consumption.store');

    // API endpoints for dynamic data (optional)
    Route::get('api/batches/{batch}/chart-data', [AnalyticsController::class, 'chartData'])->name('api.chart-data');
    Route::get('api/batches/{batch}/summary', [BatchController::class, 'summary'])->name('api.summary');
});