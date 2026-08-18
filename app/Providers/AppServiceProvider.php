<?php

namespace App\Providers;

use App\Models\Poultry\InventoryItem;
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
            $view->with([
                'unreadNotificationsCount' => NotificationService::getUnreadCount($user),
                'recentNotifications' => NotificationService::getUserNotifications($user)->take(5),
                'todayTasksCount' => \App\Models\Poultry\WeighingSchedule::whereDate('scheduled_date', today())->where('is_completed', false)->count(),
                'lowStockCount' => InventoryItem::whereColumn('quantity_in_stock', '<=', 'minimum_quantity')->count(),
            ]);
        });
    }
}