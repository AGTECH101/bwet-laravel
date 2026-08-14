<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flock_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poultry_batch_id')->constrained('poultry_batches')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('mortality')->default(0);
            $table->integer('culls')->default(0);
            $table->unsignedInteger('slaughter')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['poultry_batch_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flock_records');
    }
};