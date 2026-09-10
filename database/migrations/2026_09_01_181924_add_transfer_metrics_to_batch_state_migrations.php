<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_state_migrations', function (Blueprint $table) {
            if (!Schema::hasColumn('batch_state_migrations', 'mortality_moved')) {
                $table->decimal('mortality_moved', 10, 3)->default(0)->after('cost_moved');
            }
            if (!Schema::hasColumn('batch_state_migrations', 'feed_moved')) {
                $table->decimal('feed_moved', 12, 3)->default(0)->after('mortality_moved');
            }
            if (!Schema::hasColumn('batch_state_migrations', 'weight_gain_moved')) {
                $table->decimal('weight_gain_moved', 12, 3)->default(0)->after('feed_moved');
            }
        });
    }

    public function down(): void
    {
        Schema::table('batch_state_migrations', function (Blueprint $table) {
            $columns = ['mortality_moved', 'feed_moved', 'weight_gain_moved'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('batch_state_migrations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};