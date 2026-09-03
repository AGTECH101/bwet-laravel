<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('poultry_batches', function (Blueprint $table) {
            if (Schema::hasColumn('poultry_batches', 'pond_mortality')) {
                $table->renameColumn('pond_mortality', 'pen_mortality');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pen_mortality', function (Blueprint $table) {
            //
        });
    }
};
