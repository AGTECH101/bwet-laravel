<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flock_records', function (Blueprint $table) {
            if (!Schema::hasColumn('flock_records', 'delta_count')) {
                $table->integer('delta_count')->nullable()->after('slaughter_avg_weight');
            }
            if (!Schema::hasColumn('flock_records', 'delta_weight')) {
                $table->decimal('delta_weight', 12, 3)->nullable()->after('delta_count');
            }
            if (!Schema::hasColumn('flock_records', 'delta_mortality')) {
                $table->decimal('delta_mortality', 10, 3)->nullable()->after('delta_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flock_records', function (Blueprint $table) {
            if (Schema::hasColumn('flock_records', 'delta_count')) {
                $table->dropColumn('delta_count');
            }
            if (Schema::hasColumn('flock_records', 'delta_weight')) {
                $table->dropColumn('delta_weight');
            }
            if (Schema::hasColumn('flock_records', 'delta_mortality')) {
                $table->dropColumn('delta_mortality');
            }
        });
    }
};