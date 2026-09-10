<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flock_records', function (Blueprint $table) {
            if (!Schema::hasColumn('flock_records', 'allocated_cost')) {
                $table->decimal('allocated_cost', 14, 2)->default(0)->after('slaughter');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flock_records', function (Blueprint $table) {
            if (Schema::hasColumn('flock_records', 'allocated_cost')) {
                $table->dropColumn('allocated_cost');
            }
        });
    }
};