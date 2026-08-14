use App\Models\Poultry\Batch;
use App\Models\ObservationReport;
use App\Models\SystemVariable;
use App\Models\MarketPrice;

// In boot method
Gate::define('manage-system-variables', function ($user) {
    return $user->role === 'admin';
});

Gate::define('manage-market-prices', function ($user) {
    return $user->role === 'admin';
});

Gate::define('manage-users', function ($user) {
    return $user->role === 'admin';
});