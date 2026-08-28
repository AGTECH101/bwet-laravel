<?php

namespace App\Services\Poultry;

use App\Models\Poultry\Batch;
use App\Models\User;
use App\Models\ObservationReport;
use App\Models\Poultry\InventoryItem;
use App\Models\Notification;
use App\Models\Poultry\WeighingSchedule;
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

    /**
     * Get today's weighing tasks as an array for display.
     */
    private static function getTodayTasks(): array
    {
        $todaySchedules = WeighingSchedule::with('batch')
            ->whereDate('scheduled_date', today())
            ->where('is_completed', false)
            ->get();

        return $todaySchedules->map(function ($schedule) {
            return [
                'message' => 'Weighing scheduled for ' . $schedule->batch->batch_id,
                'icon' => 'weight',
                'batch' => $schedule->batch->batch_id . ' - ' . $schedule->batch->name,
                'action_url' => route('poultry.forms.weight-record.create', ['batch' => $schedule->batch->batch_id]),
            ];
        })->toArray();
    }

    public static function getAdminDashboard()
    {
        // Only active batches for recent display
        $allBatches = Batch::where('status', 'active')->get();
        self::refreshBatchMetrics($allBatches);

        $recentBatches = Batch::where('status', 'active')
            ->latest('created_at')
            ->limit(5)
            ->get();
        self::refreshBatchMetrics($recentBatches);

        $lowStockItems = InventoryItem::whereColumn('quantity_in_stock', '<=', 'minimum_quantity')->get();
        $outOfStockItems = InventoryItem::where('quantity_in_stock', '<=', 0)->get();

        // Average metrics from active batches
        $avgCfcr = $allBatches->isNotEmpty() ? $allBatches->where('current_cfcr', '>', 0)->avg('current_cfcr') : 0;
        $avgMortality = $allBatches->isNotEmpty()
            ? $allBatches->avg(fn ($b) => $b->starting_flock > 0 ? ($b->total_mortality / $b->starting_flock) * 100 : 0)
            : 0;

        return [
            'overview' => [
                'total_batches' => Batch::count(),
                'active_batches' => Batch::where('status', 'active')->count(),
                'completed_batches' => Batch::whereIn('status', ['closed', 'completed'])->count(),
                'total_users' => User::count(),
                'pending_approvals' => User::where('is_approved', false)->count(),
                'total_active_investment' => self::getTotalActiveInvestment(),
            ],
            'financial' => [
                'total_expenses' => Batch::where('status', 'active')->sum('total_expenses')
                    + Batch::where('status', 'active')->sum('initial_chicken_cost'),
                'inventory_value' => InventoryItem::sum(DB::raw('quantity_in_stock * cost_per_unit')),
            ],
            'alerts' => [
                'low_stock_items' => $lowStockItems->count(),
                'out_of_stock_items' => $outOfStockItems->count(),
            ],
            'recentBatches' => $recentBatches,
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
            'todayTasksCount' => WeighingSchedule::whereDate('scheduled_date', today())->where('is_completed', false)->count(),
            'todayTasks' => self::getTodayTasks(),
            'avg_cfcr' => round($avgCfcr, 3),
            'avg_mortality' => round($avgMortality, 1),
        ];
    }

    public static function getManagerDashboard(User $user)
    {
        // Only active batches
        $batches = Batch::where('status', 'active')->with('createdBy')->get();
        self::refreshBatchMetrics($batches);
        $lowStockItems = InventoryItem::whereColumn('quantity_in_stock', '<=', 'minimum_quantity')->get();
        $outOfStockItems = InventoryItem::where('quantity_in_stock', '<=', 0)->get();
        $todaySchedules = WeighingSchedule::with('batch')
            ->whereDate('scheduled_date', today())
            ->where('is_completed', false)
            ->get();

        $observations = [
            'my_recent' => ObservationReport::query()->latest('created_at')->limit(5)->get(),
        ];

        $avgMortality = $batches->isNotEmpty()
            ? $batches->avg(fn ($batch) => $batch->starting_flock > 0 ? (($batch->total_mortality / $batch->starting_flock) * 100) : 0)
            : 0;
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
            'todayTasks' => self::getTodayTasks(),
            'lowStockCount' => $lowStockItems->count(),
            'unreadNotificationsCount' => Notification::where('is_active', true)->count(),
            'recentNotifications' => [],
        ];
    }

    public static function getStaffDashboard(User $user)
    {
        // Only active batches created by this staff
        $activeBatches = Batch::where('created_by_id', $user->id)->where('status', 'active')->get();
        $todaySchedules = WeighingSchedule::with('batch')
            ->whereDate('scheduled_date', today())
            ->where('is_completed', false)
            ->get();
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
            'recentFlock' => $flockRecords,
            'recentWeight' => $weightRecords,
            'recentFeed' => $feedRecords,
            'todayTasksCount' => $todaySchedules->count(),
            'todaySchedules' => $todaySchedules,
            'todayTasks' => self::getTodayTasks(),
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
            'todayTasks' => [],
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