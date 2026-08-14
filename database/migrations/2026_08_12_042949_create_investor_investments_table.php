<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('poultry_batch_id')->constrained('poultry_batches')->cascadeOnDelete();
            $table->decimal('amount_invested', 14, 2);
            $table->date('investment_date');
            $table->decimal('batch_total_cost_at_investment', 14, 2);
            $table->decimal('investment_percentage', 10, 4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_investments');
    }
};