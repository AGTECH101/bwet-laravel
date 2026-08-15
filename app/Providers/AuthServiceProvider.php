<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            if ($user && strtolower((string) $user->role) === 'admin') {
                return true;
            }
        });

        Gate::define('viewAny', function ($user, $model = null) {
            return in_array(strtolower((string) $user->role), ['admin', 'manager', 'staff'], true);
        });

        Gate::define('view', function ($user, $model = null) {
            return in_array(strtolower((string) $user->role), ['admin', 'manager', 'staff'], true);
        });

        Gate::define('create', function ($user, $model = null) {
            return in_array(strtolower((string) $user->role), ['admin', 'manager', 'staff'], true);
        });

        Gate::define('update', function ($user, $model = null) {
            return in_array(strtolower((string) $user->role), ['admin', 'manager', 'staff'], true);
        });

        Gate::define('delete', function ($user, $model = null) {
            return in_array(strtolower((string) $user->role), ['admin', 'manager'], true);
        });

        Gate::define('export', function ($user) {
            return in_array(strtolower((string) $user->role), ['admin', 'manager'], true);
        });

        Gate::define('manage-system-variables', function ($user) {
            return strtolower((string) $user->role) === 'admin';
        });

        Gate::define('manage-market-prices', function ($user) {
            return in_array(strtolower((string) $user->role), ['admin', 'manager'], true);
        });

        Gate::define('manage-users', function ($user) {
            return in_array(strtolower((string) $user->role), ['admin', 'manager'], true);
        });
    }
}