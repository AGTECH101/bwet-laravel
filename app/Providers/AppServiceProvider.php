<?php

namespace App\Providers;

use App\Models\Poultry\InventoryItem;
use App\Models\Poultry\WeighingSchedule;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (($this->app->environment('production') || filter_var(env('APP_FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN)) && ! $this->app->runningInConsole()) {
            URL::forceScheme('https');
        }

        View::composer('*', function ($view) {
            if (! Auth::check()) {
                return;
            }

            $user = Auth::user();

            // Get today's weighing schedules for tasks
            $todaySchedules = WeighingSchedule::with('batch')
                ->whereDate('scheduled_date', today())
                ->where('is_completed', false)
                ->get();

            $todayTasks = $todaySchedules->map(function ($schedule) {
                return [
                    'message' => 'Weighing scheduled for ' . $schedule->batch->batch_id,
                    'icon' => 'weight',
                    'batch' => $schedule->batch->batch_id . ' - ' . $schedule->batch->name,
                    'action_url' => route('poultry.forms.weight-record.create', ['batch' => $schedule->batch->batch_id]),
                ];
            })->toArray();

            $view->with([
                'unreadNotificationsCount' => NotificationService::getUnreadCount($user),
                'recentNotifications' => NotificationService::getUserNotifications($user)->take(5),
                'todayTasksCount' => $todaySchedules->count(),
                'todayTasks' => $todayTasks,
                'lowStockCount' => InventoryItem::whereColumn('quantity_in_stock', '<=', 'minimum_quantity')->count(),
            ]);
        });
    }
}