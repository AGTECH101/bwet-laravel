<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poultry_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->unique();
            $table->string('name');
            $table->string('hatchery')->nullable();
            $table->date('start_date');
            $table->unsignedInteger('starting_flock');
            $table->unsignedInteger('remaining_flock')->default(0);
            $table->enum('phase', ['brooding', 'batch'])->default('batch');
            $table->foreignId('pen_id')->nullable()->constrained('pens')->nullOnDelete();
            $table->decimal('selling_price_per_kg', 10, 2)->default(0);
            $table->decimal('selling_price_per_carton', 10, 2)->default(0);
            $table->decimal('initial_chicken_cost', 12, 2)->default(0);
            $table->enum('status', ['active', 'closed', 'completed'])->default('active');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('current_age_days')->default(0);
            $table->unsignedInteger('total_mortality')->default(0);
            $table->integer('total_culls')->default(0);
            $table->unsignedInteger('total_slaughter')->default(0);
            $table->decimal('total_feed_used', 12, 3)->default(0);
            $table->decimal('bags_consumed', 10, 2)->default(0);
            $table->decimal('total_weight_gain', 12, 3)->default(0);
            $table->decimal('current_ifcr', 10, 4)->default(0);
            $table->decimal('current_cfcr', 10, 4)->default(0);
            $table->decimal('current_marginal_profit_percent', 10, 2)->default(0);
            $table->decimal('total_expenses', 14, 2)->default(0);
            $table->decimal('cost_allocated_so_far', 14, 2)->default(0);
            $table->decimal('peak_profit', 14, 2)->default(0);
            $table->decimal('profit_margin_used', 10, 2)->default(0);
            $table->decimal('stop_loss_used_percent', 10, 2)->default(0);
            $table->boolean('is_manual_mode')->default(false);
            $table->text('manual_mode_reason')->nullable();
            $table->foreignId('manual_mode_enabled_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manual_mode_enabled_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sector_id')->constrained('sectors');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poultry_batches');
    }
};