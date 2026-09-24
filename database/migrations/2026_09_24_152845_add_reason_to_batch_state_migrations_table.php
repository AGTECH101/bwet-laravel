<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('batch_state_migrations')) {
            return;
        }

        Schema::table('batch_state_migrations', function (Blueprint $table) {
            if (! Schema::hasColumn('batch_state_migrations', 'reason')) {
                $table->string('reason')->nullable()->after('source_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('batch_state_migrations')) {
            return;
        }

        Schema::table('batch_state_migrations', function (Blueprint $table) {
            if (Schema::hasColumn('batch_state_migrations', 'reason')) {
                $table->dropColumn('reason');
            }
        });
    }
};