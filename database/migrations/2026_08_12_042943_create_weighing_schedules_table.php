<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weighing_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poultry_batch_id')->constrained('poultry_batches')->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->boolean('admin_notified_missed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weighing_schedules');
    }
};