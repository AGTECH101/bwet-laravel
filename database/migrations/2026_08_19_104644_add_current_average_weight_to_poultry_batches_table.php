<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('poultry_batches', 'current_average_weight')) {
                $table->decimal('current_average_weight', 10, 3)->default(0)->after('remaining_flock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('poultry_batches', function (Blueprint $table) {
            if (Schema::hasColumn('poultry_batches', 'current_average_weight')) {
                $table->dropColumn('current_average_weight');
            }
        });
    }
};