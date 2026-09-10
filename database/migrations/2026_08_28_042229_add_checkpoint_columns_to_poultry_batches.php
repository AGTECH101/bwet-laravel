<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_batches', function (Blueprint $table) {
            // Only add columns that don't already exist
            if (!Schema::hasColumn('poultry_batches', 'current_count')) {
                $table->unsignedInteger('current_count')->default(0)->after('remaining_flock');
            }
            if (!Schema::hasColumn('poultry_batches', 'current_weight_kg')) {
                $table->decimal('current_weight_kg', 12, 3)->default(0)->after('current_count');
            }
            if (!Schema::hasColumn('poultry_batches', 'current_cost')) {
                $table->decimal('current_cost', 14, 2)->default(0)->after('current_weight_kg');
            }
            // current_average_weight already exists, so we skip it
            if (!Schema::hasColumn('poultry_batches', 'current_average_cost')) {
                $table->decimal('current_average_cost', 10, 2)->default(0)->after('current_average_weight');
            }
            if (!Schema::hasColumn('poultry_batches', 'total_weight_gain')) {
                $table->decimal('total_weight_gain', 12, 3)->default(0)->after('current_average_cost');
            }
        });
    }

    public function down(): void
    {
        Schema::table('poultry_batches', function (Blueprint $table) {
            // Drop only if they exist
            $columns = ['current_count', 'current_weight_kg', 'current_cost', 'current_average_cost', 'total_weight_gain'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('poultry_batches', $col)) {
                    $table->dropColumn($col);
                }
            }
            // Do not drop current_average_weight because it existed before
        });
    }
};