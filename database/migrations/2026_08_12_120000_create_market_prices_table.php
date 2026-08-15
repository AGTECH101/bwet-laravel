<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('market_prices')) {
            return;
        }

        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_per_bird', 12, 2)->default(0);
            $table->decimal('price_per_kg', 12, 2)->default(0);
            $table->decimal('price_per_carton', 12, 2)->default(0);
            $table->date('effective_date');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(false);
            $table->foreignId('set_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_prices');
    }
};
