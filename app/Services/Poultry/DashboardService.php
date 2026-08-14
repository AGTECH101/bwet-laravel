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
    public static function getAdminDashboard()
    {
        // Similar to Django's DashboardService.get_admin_dashboard
        // Return array of stats, charts, etc.
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
            // ... more fields
        ];
    }

    public static function getManagerDashboard(User $user)
    {
        // Manager-specific data
        return [
            'batch_stats' => Batch::where('status', 'active')->get()->map(fn($b) => [
                'id' => $b->batch_id,
                'name' => $b->name,
                'age' => $b->current_age_days,
                'remaining' => $b->remaining_flock,
                'ifcr' => $b->current_ifcr,
                'cfcr' => $b->current_cfcr,
                'profit_percent' => $b->current_marginal_profit_percent,
            ]),
            'alerts' => [
                'low_stock' => InventoryItem::where('quantity_in_stock', '<=', DB::raw('minimum_quantity'))->count(),
                'today_weighings' => \App\Models\Poultry\WeighingSchedule::where('scheduled_date', now()->toDateString())->where('is_completed', false)->count(),
            ],
            // ...
        ];
    }

    public static function getStaffDashboard(User $user)
    {
        // Staff-specific data
        return [
            'active_batches' => Batch::where('created_by_id', $user->id)->where('status', 'active')->get(),
            'recent_flock' => \App\Models\Poultry\FlockRecord::where('recorded_by_id', $user->id)->latest()->limit(5)->get(),
            // ...
        ];
    }

    public static function getInvestorDashboard(User $user)
    {
        // Investor-specific data: only their investments
        $investments = $user->investorInvestments()->with('batch')->get();
        return [
            'investments' => $investments,
            'total_invested' => $investments->sum('amount_invested'),
            // ...
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