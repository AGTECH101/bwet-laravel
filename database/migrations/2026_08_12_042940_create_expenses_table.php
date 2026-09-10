<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poultry_batch_id')->nullable()->constrained('poultry_batches')->cascadeOnDelete();
            $table->date('date');
            $table->enum('category', ['medication', 'vaccination', 'labor', 'utilities', 'maintenance', 'transport', 'packaging', 'other']);
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('receipt_number')->nullable();
            $table->string('vendor')->nullable();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};