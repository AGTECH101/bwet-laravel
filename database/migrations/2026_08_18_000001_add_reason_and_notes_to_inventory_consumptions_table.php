<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_consumptions', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_consumptions', 'reason')) {
                $table->string('reason')->nullable()->after('date');
            }

            if (!Schema::hasColumn('inventory_consumptions', 'notes')) {
                $table->text('notes')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_consumptions', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_consumptions', 'notes')) {
                $table->dropColumn('notes');
            }

            if (Schema::hasColumn('inventory_consumptions', 'reason')) {
                $table->dropColumn('reason');
            }
        });
    }
};
