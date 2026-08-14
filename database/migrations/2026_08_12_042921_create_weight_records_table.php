<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weight_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poultry_batch_id')->constrained('poultry_batches')->cascadeOnDelete();
            $table->date('date');
            $table->json('individual_weights');
            $table->unsignedInteger('birds_weighed')->default(0);
            $table->decimal('total_weight', 12, 3)->default(0);
            $table->decimal('average_weight', 10, 3)->default(0);
            $table->decimal('coefficient_variation', 5, 2)->default(0);
            $table->enum('cv_status', ['excellent', 'caution', 'warning', 'rejected'])->default('excellent');
            $table->boolean('is_valid_sample')->default(true);
            $table->decimal('expected_weight', 10, 3)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['poultry_batch_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weight_records');
    }
};