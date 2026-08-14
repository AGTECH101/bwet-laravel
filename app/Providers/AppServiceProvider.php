<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginViewResponse;
use Laravel\Fortify\Http\Responses\SimpleViewResponse;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //

        $this->app->bind(LoginViewResponse::class, function () {
                return new SimpleViewResponse('auth.login'); // or 'auth.login' if you've copied views there
        });
    }
    

    public function boot(): void
    {
        // Observers can be registered here instead, but we use EventServiceProvider above.
    }
}