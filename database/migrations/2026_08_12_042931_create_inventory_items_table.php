<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('category', ['feed', 'vaccine', 'medicine', 'consumables', 'packaging', 'other']);
            $table->enum('unit', ['kg', 'g', 'l', 'ml', 'unit', 'bag', 'spoon', 'box']);
            $table->decimal('quantity_in_stock', 12, 3)->default(0);
            $table->decimal('quantity_used', 12, 3)->default(0);
            $table->decimal('minimum_quantity', 12, 3)->default(0);
            $table->string('vendor')->nullable();
            $table->decimal('cost_per_unit', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['active', 'killed'])->default('active');
            $table->text('killed_reason')->nullable();
            $table->foreignId('killed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('killed_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};