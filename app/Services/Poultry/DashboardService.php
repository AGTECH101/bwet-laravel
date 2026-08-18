<?php

namespace App\Services\Poultry;

use App\Models\Poultry\Batch;
use App\Models\User;
use App\Models\ObservationReport;
use App\Models\Poultry\InventoryItem;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private static function refreshBatchMetrics($batches): void
    {
        if ($batches instanceof \Illuminate\Database\Eloquent\Collection) {
            $batches->each(fn (Batch $batch) => $batch->updateCachedMetrics());
            return;
        }

        if ($batches instanceof Batch) {
            $batches->updateCachedMetrics();
        }
    }

    public static function getAdminDashboard()
    {
        $allBatches = Batch::query()->get();
        self::refreshBatchMetrics($allBatches);

        $recentBatches = Batch::query()->latest('created_at')->limit(5)->get();
        self::refreshBatchMetrics($recentBatches);

        $lowStockItems = InventoryItem::whereColumn('quantity_in_stock', '<=', 'minimum_quantity')->get();
        $outOfStockItems = InventoryItem::where('quantity_in_stock', '<=', 0)->get();

        return [
            'overview' => [
                'total_batches' => Batch::count(),
                'active_batches' => Batch::where('status', 'active')->count(),
                'total_users' => User::count(),
                'pending_approvals' => User::where('is_approved', false)->count(),
                'total_active_investment' => self::getTotalActiveInvestment(),
            ],
            'financial' => [
                'total_expenses' => Batch::sum('total_expenses') + Batch::sum('initial_chicken_cost'),
                'inventory_value' => InventoryItem::sum(DB::raw('quantity_in_stock * cost_per_unit')),
            ],
            'alerts' => [
                'low_stock_items' => $lowStockItems->count(),
                'out_of_stock_items' => $outOfStockItems->count(),
            ],
            'recentBatches' => $recentBatches,
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
            'todayTasksCount' => \App\Models\Poultry\WeighingSchedule::whereDate('scheduled_date', today())->where('is_completed', false)->count(),
        ];
    }

    public static function getManagerDashboard(User $user)
    {
        $batches = Batch::where('status', 'active')->with('createdBy')->get();
        self::refreshBatchMetrics($batches);
        $lowStockItems = InventoryItem::whereColumn('quantity_in_stock', '<=', 'minimum_quantity')->get();
        $outOfStockItems = InventoryItem::where('quantity_in_stock', '<=', 0)->get();
        $todaySchedules = \App\Models\Poultry\WeighingSchedule::with('batch')->whereDate('scheduled_date', today())->where('is_completed', false)->get();
        $observations = [
            'my_recent' => \App\Models\ObservationReport::query()->latest('created_at')->limit(5)->get(),
        ];

        $avgMortality = $batches->isNotEmpty() ? $batches->avg(fn ($batch) => $batch->starting_flock > 0 ? (($batch->total_mortality / $batch->starting_flock) * 100) : 0) : 0;
        $avgCfcr = $batches->isNotEmpty() ? $batches->avg('current_cfcr') : 0;
        $totalFeedUsed = $batches->sum('total_feed_used');

        return [
            'batches' => $batches,
            'overall_metrics' => [
                'avg_mortality' => $avgMortality,
                'avg_cfcr' => $avgCfcr,
                'total_feed_used' => $totalFeedUsed,
            ],
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
            'observations' => $observations,
            'todayTasksCount' => $todaySchedules->count(),
            'todaySchedules' => $todaySchedules,
            'lowStockCount' => $lowStockItems->count(),
            'unreadNotificationsCount' => Notification::where('is_active', true)->count(),
            'recentNotifications' => [],
        ];
    }

    public static function getStaffDashboard(User $user)
    {
        $activeBatches = Batch::where('created_by_id', $user->id)->where('status', 'active')->get();
        $todaySchedules = \App\Models\Poultry\WeighingSchedule::with('batch')->whereDate('scheduled_date', today())->where('is_completed', false)->get();
        $flockRecords = \App\Models\Poultry\FlockRecord::where('recorded_by_id', $user->id)->latest()->limit(5)->get();
        $weightRecords = \App\Models\Poultry\WeightRecord::where('recorded_by_id', $user->id)->latest()->limit(5)->get();
        $feedRecords = \App\Models\Poultry\FeedRecord::where('recorded_by_id', $user->id)->latest()->limit(5)->get();

        return [
            'stats' => [
                'active_batches' => $activeBatches->count(),
                'batches_created' => Batch::where('created_by_id', $user->id)->count(),
                'flock_records' => \App\Models\Poultry\FlockRecord::where('recorded_by_id', $user->id)->count(),
                'weight_records' => \App\Models\Poultry\WeightRecord::where('recorded_by_id', $user->id)->count(),
                'feed_records' => \App\Models\Poultry\FeedRecord::where('recorded_by_id', $user->id)->count(),
                'last_flock_date' => \App\Models\Poultry\FlockRecord::where('recorded_by_id', $user->id)->latest('date')->value('date'),
                'last_weight_date' => \App\Models\Poultry\WeightRecord::where('recorded_by_id', $user->id)->latest('date')->value('date'),
                'last_feed_date' => \App\Models\Poultry\FeedRecord::where('recorded_by_id', $user->id)->latest('date')->value('date'),
            ],
            'active_batches' => $activeBatches,
            'recent_flock' => $flockRecords,
            'todayTasksCount' => $todaySchedules->count(),
            'todaySchedules' => $todaySchedules,
            'lowStockCount' => InventoryItem::whereColumn('quantity_in_stock', '<=', 'minimum_quantity')->count(),
            'unreadNotificationsCount' => Notification::where('is_active', true)->count(),
            'recentNotifications' => [],
        ];
    }

    public static function getInvestorDashboard(User $user)
    {
        $investments = $user->investorInvestments()->with('batch')->get();
        return [
            'investments' => $investments,
            'total_invested' => $investments->sum('amount_invested'),
            'todayTasksCount' => 0,
            'lowStockCount' => 0,
            'unreadNotificationsCount' => 0,
            'recentNotifications' => [],
        ];
    }

    private static function getTotalActiveInvestment(): float
    {
        return Batch::where('status', 'active')->get()->sum(function ($batch) {
            return BatchCalculationService::calculateTotalInvestment($batch);
        });
    }

    public static function getDashboardData($user, $role)
    {
        return match ($role) {
            'admin' => self::getAdminDashboard(),
            'manager' => self::getManagerDashboard($user),
            'staff' => self::getStaffDashboard($user),
            'investor' => self::getInvestorDashboard($user),
            default => [],
        };
    }
}