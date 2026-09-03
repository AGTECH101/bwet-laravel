<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_batches', function (Blueprint $table) {
            // Checkpoint columns (if not already present)
            if (!Schema::hasColumn('poultry_batches', 'current_count')) {
                $table->unsignedInteger('current_count')->default(0)->after('remaining_flock');
            }
            if (!Schema::hasColumn('poultry_batches', 'current_weight_kg')) {
                $table->decimal('current_weight_kg', 12, 3)->default(0)->after('current_count');
            }
            if (!Schema::hasColumn('poultry_batches', 'current_cost')) {
                $table->decimal('current_cost', 14, 2)->default(0)->after('current_weight_kg');
            }
            if (!Schema::hasColumn('poultry_batches', 'current_average_cost')) {
                $table->decimal('current_average_cost', 10, 2)->default(0)->after('current_average_weight');
            }
            if (!Schema::hasColumn('poultry_batches', 'total_weight_gain')) {
                $table->decimal('total_weight_gain', 12, 3)->default(0)->after('current_average_cost');
            }

            // Mortality tracking columns
            if (!Schema::hasColumn('poultry_batches', 'historical_mortality')) {
                $table->decimal('historical_mortality', 10, 3)->default(0)->after('total_mortality');
            }
            if (!Schema::hasColumn('poultry_batches', 'pond_mortality')) {
                $table->decimal('pond_mortality', 10, 3)->default(0)->after('historical_mortality');
            }
            if (!Schema::hasColumn('poultry_batches', 'mortality_rate')) {
                $table->decimal('mortality_rate', 10, 2)->default(0)->after('pond_mortality');
            }
        });
    }

    public function down(): void
    {
        Schema::table('poultry_batches', function (Blueprint $table) {
            $columns = [
                'current_count', 'current_weight_kg', 'current_cost',
                'current_average_cost', 'total_weight_gain',
                'historical_mortality', 'pond_mortality', 'mortality_rate'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('poultry_batches', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};