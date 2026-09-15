<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Keep the most recent row per key, delete the older ones.
        //    "Most recent" = highest id, which correlates with latest insert.
        $keys = DB::table('system_variables')
            ->select('key')
            ->groupBy('key')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('key');

        foreach ($keys as $key) {
            $keepId = DB::table('system_variables')
                ->where('key', $key)
                ->orderByDesc('id')
                ->value('id');

            DB::table('system_variables')
                ->where('key', $key)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        // 2. Add a unique constraint so this can never happen again.
        //    Wrap in a try/catch since some databases may already have it
        //    (e.g., if migrations are re-run on a fresh install).
        try {
            Schema::table('system_variables', function (Blueprint $table) {
                $table->unique('key', 'system_variables_key_unique');
            });
        } catch (\Throwable $e) {
            // Constraint already exists — safe to continue.
        }
    }

    public function down(): void
    {
        Schema::table('system_variables', function (Blueprint $table) {
            try {
                $table->dropUnique('system_variables_key_unique');
            } catch (\Throwable $e) {
                // Not present — safe to continue.
            }
        });
    }
};