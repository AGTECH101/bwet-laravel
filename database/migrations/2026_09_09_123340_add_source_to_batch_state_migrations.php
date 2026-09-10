<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_state_migrations', function (Blueprint $table) {
            if (!Schema::hasColumn('batch_state_migrations', 'source_type')) {
                $table->string('source_type')->nullable()->after('migration_type');
            }
            if (!Schema::hasColumn('batch_state_migrations', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('batch_state_migrations', function (Blueprint $table) {
            if (Schema::hasColumn('batch_state_migrations', 'source_type')) {
                $table->dropColumn('source_type');
            }
            if (Schema::hasColumn('batch_state_migrations', 'source_id')) {
                $table->dropColumn('source_id');
            }
        });
    }
};