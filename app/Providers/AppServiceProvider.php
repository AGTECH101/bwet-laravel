<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
    }
}