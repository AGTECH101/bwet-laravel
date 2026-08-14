<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poultry_batch_id')->constrained('poultry_batches')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('age_days');
            $table->decimal('average_weight', 10, 3);
            $table->decimal('daily_feed', 10, 3);
            $table->decimal('cumulative_feed', 12, 3);
            $table->decimal('ifcr', 10, 4);
            $table->decimal('cfcr', 10, 4);
            $table->decimal('marginal_profit_percent', 10, 2);
            $table->decimal('adg', 10, 4)->nullable();
            $table->timestamps();
            $table->unique(['poultry_batch_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
    }
};