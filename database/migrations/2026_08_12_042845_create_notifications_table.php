<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type', 50);
            $table->string('title');
            $table->text('message');
            $table->foreignId('batch_id')->nullable()->constrained('poultry_batches')->nullOnDelete();
            $table->foreignId('observation_report_id')->nullable()->constrained('observation_reports')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};