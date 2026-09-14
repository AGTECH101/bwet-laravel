<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // 1) Drop the UNIQUE(poultry_batch_id, date) on flock_records
        //    so multiple entries per day are allowed.
        // ============================================================
        try {
            Schema::table('flock_records', function (Blueprint $table) {
                $table->dropUnique('flock_records_poultry_batch_id_date_unique');
            });
        } catch (\Throwable $e) {
            // Already dropped — safe to continue.
        }

        // ============================================================
        // 2) Widen the cv_status enum to include 'high'.
        //    Laravel's ->change() handles both SQLite (table rebuild)
        //    and MySQL (ALTER TABLE MODIFY COLUMN) natively.
        // ============================================================
        Schema::table('weight_records', function (Blueprint $table) {
            $table->enum('cv_status', [
                'excellent',
                'caution',
                'warning',
                'high',
                'rejected', // kept for backwards compatibility; legacy rows migrated below
            ])->default('excellent')->change();
        });

        // ============================================================
        // 3) Migrate legacy data: 'rejected' → 'high'.
        //    Safe now because the constraint allows 'high'.
        // ============================================================
        DB::table('weight_records')
            ->where('cv_status', 'rejected')
            ->update(['cv_status' => 'high']);
    }

    public function down(): void
    {
        // Revert data
        DB::table('weight_records')
            ->where('cv_status', 'high')
            ->update(['cv_status' => 'rejected']);

        // Restore original enum
        Schema::table('weight_records', function (Blueprint $table) {
            $table->enum('cv_status', ['excellent', 'caution', 'warning', 'rejected'])
                  ->default('excellent')
                  ->change();
        });

        // Restore unique constraint
        Schema::table('flock_records', function (Blueprint $table) {
            $table->unique(['poultry_batch_id', 'date'], 'flock_records_poultry_batch_id_date_unique');
        });
    }
};