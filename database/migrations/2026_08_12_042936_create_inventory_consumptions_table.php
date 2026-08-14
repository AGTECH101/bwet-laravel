<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('poultry_batch_id')->nullable()->constrained('poultry_batches')->nullOnDelete();
            $table->decimal('quantity_used', 12, 3);
            $table->date('date');
            $table->decimal('unit_cost_at_time', 10, 2);
            $table->decimal('total_cost', 12, 2);
            $table->enum('source_type', ['manual', 'feed', 'expense'])->default('manual');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_consumptions');
    }
};