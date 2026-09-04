<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flock_records', function (Blueprint $table) {
            if (!Schema::hasColumn('flock_records', 'slaughter_avg_weight')) {
                $table->decimal('slaughter_avg_weight', 10, 3)->nullable()->after('slaughter');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flock_records', function (Blueprint $table) {
            if (Schema::hasColumn('flock_records', 'slaughter_avg_weight')) {
                $table->dropColumn('slaughter_avg_weight');
            }
        });
    }
};