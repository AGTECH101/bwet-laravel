<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Models\NotificationReadStatus;
use App\Models\Poultry\Batch;
use App\Models\User;
use App\Services\Poultry\BatchCalculationService;
use App\Services\Poultry\DashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BatchCalculationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $tables = [
            'users', 'sectors', 'poultry_batches', 'inventory_items', 'weighing_schedules',
            'system_variables', 'flock_records', 'feed_records', 'weight_records', 'expenses',
            'notifications', 'notification_read_statuses',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->createTableFor($table);
            }
        }
    }

    protected function createTableFor(string $table): void
    {
        switch ($table) {
            case 'users':
                Schema::create('users', function ($table) {
                    $table->id();
                    $table->string('name');
                    $table->string('email')->unique();
                    $table->boolean('is_approved')->default(true);
                    $table->timestamps();
                });
                break;

            case 'sectors':
                Schema::create('sectors', function ($table) {
                    $table->id();
                    $table->string('name');
                    $table->string('slug');
                    $table->timestamps();
                });
                break;

            case 'poultry_batches':
                Schema::create('poultry_batches', function ($table) {
                    $table->id();
                    $table->string('batch_id')->unique();
                    $table->string('name');
                    $table->date('start_date');
                    $table->unsignedInteger('starting_flock')->default(0);
                    $table->unsignedInteger('remaining_flock')->default(0);
                    $table->decimal('selling_price_per_kg', 10, 2)->default(0);
                    $table->decimal('selling_price_per_carton', 10, 2)->default(0);
                    $table->decimal('initial_chicken_cost', 12, 2)->default(0);
                    $table->string('status')->default('active');
                    $table->unsignedInteger('current_age_days')->default(0);
                    $table->unsignedInteger('total_mortality')->default(0);
                    $table->unsignedInteger('total_culls')->default(0);
                    $table->unsignedInteger('total_slaughter')->default(0);
                    $table->decimal('total_feed_used', 12, 3)->default(0);
                    $table->decimal('bags_consumed', 10, 2)->default(0);
                    $table->decimal('total_weight_gain', 12, 3)->default(0);
                    $table->decimal('current_ifcr', 10, 4)->default(0);
                    $table->decimal('current_cfcr', 10, 4)->default(0);
                    $table->decimal('current_marginal_profit_percent', 10, 2)->default(0);
                    $table->decimal('total_expenses', 12, 2)->default(0);
                    $table->decimal('cost_allocated_so_far', 12, 2)->default(0);
                    $table->decimal('peak_profit', 12, 2)->default(0);
                    $table->decimal('profit_margin_used', 10, 2)->default(0);
                    $table->decimal('stop_loss_used_percent', 10, 2)->default(0);
                    $table->boolean('is_manual_mode')->default(false);
                    $table->bigInteger('sector_id')->nullable();
                    $table->bigInteger('created_by_id')->nullable();
                    $table->timestamps();
                });
                break;

            case 'inventory_items':
                Schema::create('inventory_items', function ($table) {
                    $table->id();
                    $table->string('name');
                    $table->string('unit');
                    $table->decimal('quantity_in_stock', 12, 3)->default(0);
                    $table->decimal('minimum_quantity', 12, 3)->default(0);
                    $table->decimal('cost_per_unit', 12, 2)->default(0);
                    $table->timestamps();
                });
                break;

            case 'weighing_schedules':
                Schema::create('weighing_schedules', function ($table) {
                    $table->id();
                    $table->bigInteger('poultry_batch_id')->nullable();
                    $table->date('scheduled_date');
                    $table->boolean('is_completed')->default(false);
                    $table->timestamps();
                });
                break;

            case 'system_variables':
                Schema::create('system_variables', function ($table) {
                    $table->id();
                    $table->string('key');
                    $table->string('name')->nullable();
                    $table->string('data_type')->default('decimal');
                    $table->decimal('value', 12, 4)->default(0);
                    $table->boolean('is_active')->default(true);
                    $table->timestamp('effective_from')->nullable();
                    $table->timestamps();
                });
                break;

            case 'flock_records':
                Schema::create('flock_records', function ($table) {
                    $table->id();
                    $table->bigInteger('poultry_batch_id')->nullable();
                    $table->unsignedInteger('mortality')->default(0);
                    $table->unsignedInteger('culls')->default(0);
                    $table->unsignedInteger('slaughter')->default(0);
                    $table->timestamps();
                });
                break;

            case 'feed_records':
                Schema::create('feed_records', function ($table) {
                    $table->id();
                    $table->bigInteger('poultry_batch_id')->nullable();
                    $table->date('date');
                    $table->decimal('feed_used', 12, 3)->default(0);
                    $table->decimal('feed_cost_per_kg', 12, 2)->default(0);
                    $table->timestamps();
                });
                break;

            case 'weight_records':
                Schema::create('weight_records', function ($table) {
                    $table->id();
                    $table->bigInteger('poultry_batch_id')->nullable();
                    $table->date('date');
                    $table->decimal('average_weight', 12, 3)->default(0);
                    $table->timestamps();
                });
                break;

            case 'expenses':
                Schema::create('expenses', function ($table) {
                    $table->id();
                    $table->bigInteger('poultry_batch_id')->nullable();
                    $table->decimal('amount', 12, 2)->default(0);
                    $table->timestamps();
                });
                break;

            case 'notifications':
                Schema::create('notifications', function ($table) {
                    $table->id();
                    $table->string('notification_type');
                    $table->string('title');
                    $table->text('message');
                    $table->boolean('is_active')->default(true);
                    $table->unsignedBigInteger('batch_id')->nullable();
                    $table->unsignedBigInteger('observation_report_id')->nullable();
                    $table->unsignedBigInteger('created_by_id')->nullable();
                    $table->timestamps();
                });
                break;

            case 'notification_read_statuses':
                Schema::create('notification_read_statuses', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id');
                    $table->unsignedBigInteger('notification_id');
                    $table->boolean('is_read')->default(false);
                    $table->timestamp('read_at')->nullable();
                    $table->timestamps();
                });
                break;
        }
    }

    public function test_required_sample_size_handles_missing_flock_value(): void
    {
        $this->assertSame(0, BatchCalculationService::calculateRequiredSampleSize(null));
        $this->assertSame(5, BatchCalculationService::calculateRequiredSampleSize(25));
    }

    public function test_batch_model_can_calculate_total_investment_without_data(): void
    {
        $batch = new Batch();

        $this->assertSame(0.0, $batch->calculateTotalInvestment());
    }

    public function test_admin_dashboard_refreshes_stale_batch_metrics_from_database(): void
    {
        $batch = Batch::create([
            'batch_id' => 'B1000',
            'name' => 'Test Batch',
            'start_date' => Carbon::today()->subDays(3),
            'starting_flock' => 200,
            'remaining_flock' => 180,
            'selling_price_per_kg' => 0,
            'selling_price_per_carton' => 0,
            'initial_chicken_cost' => 5000,
            'status' => 'active',
            'current_age_days' => 999,
            'current_cfcr' => 0,
            'total_mortality' => 0,
            'total_culls' => 0,
            'total_slaughter' => 0,
            'total_feed_used' => 0,
            'total_expenses' => 0,
            'cost_allocated_so_far' => 0,
            'sector_id' => 1,
            'created_by_id' => 1,
        ]);

        $freshAge = max(0, Carbon::today()->diffInDays($batch->start_date));

        $dashboard = DashboardService::getAdminDashboard();
        $refreshedAge = $dashboard['recentBatches']->first()->current_age_days;

        $this->assertSame($freshAge, $refreshedAge);
        $this->assertNotSame(999, $refreshedAge);
    }

    public function test_user_has_notification_read_status_relationship_for_clear_all(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_approved' => true,
        ]);

        $notification = Notification::create([
            'notification_type' => 'weighing_day',
            'title' => 'Weighing due',
            'message' => 'A weighing is due today.',
            'is_active' => true,
            'created_by_id' => $user->id,
        ]);

        NotificationReadStatus::create([
            'user_id' => $user->id,
            'notification_id' => $notification->id,
            'is_read' => false,
        ]);

        $this->assertSame(1, $user->notificationReadStatuses()->count());
    }

    public function test_batch_fcr_uses_zero_when_data_is_missing_or_invalid(): void
    {
        $batch = Batch::create([
            'batch_id' => 'B2000',
            'name' => 'FCR Batch',
            'start_date' => Carbon::today()->subDays(10),
            'starting_flock' => 100,
            'remaining_flock' => 90,
            'initial_chicken_cost' => 12000,
            'status' => 'active',
            'sector_id' => 1,
            'created_by_id' => 1,
        ]);

        $batch->weightRecords()->create([
            'date' => Carbon::today()->subDays(2),
            'average_weight' => 1.4,
        ]);

        $batch->weightRecords()->create([
            'date' => Carbon::today(),
            'average_weight' => 1.4,
        ]);

        $batch->feedRecords()->create([
            'date' => Carbon::today()->subDays(2),
            'feed_used' => 10,
            'feed_cost_per_kg' => 100,
        ]);

        $batch->feedRecords()->create([
            'date' => Carbon::today(),
            'feed_used' => 10,
            'feed_cost_per_kg' => 100,
        ]);

        $batch->updateCachedMetrics();

        $this->assertSame(0.0, (float) $batch->current_ifcr);
        $this->assertSame(0.0, (float) $batch->current_cfcr);
    }
}
