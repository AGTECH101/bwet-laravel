<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    protected $observers = [
        \App\Models\Poultry\InventoryConsumption::class => [
            \App\Observers\Poultry\InventoryConsumptionObserver::class,
        ],
        \App\Models\Poultry\FeedRecord::class => [
            \App\Observers\Poultry\FeedRecordObserver::class,
        ],
        \App\Models\Poultry\Batch::class => [
            \App\Observers\Poultry\BatchObserver::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}