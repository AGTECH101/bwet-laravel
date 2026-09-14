<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_batches', function (Blueprint $table) {
            $table->string('batch_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Note: a reverse migration can only run if there are no NULL batch_ids.
        Schema::table('poultry_batches', function (Blueprint $table) {
            $table->string('batch_id')->nullable(false)->change();
        });
    }
};