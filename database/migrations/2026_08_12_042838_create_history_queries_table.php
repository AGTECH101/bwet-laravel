<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history_queries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('query_type', 20);
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->foreignId('user_filter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('batch_filter_id')->nullable()->constrained('poultry_batches')->nullOnDelete();
            $table->string('category_filter')->nullable();
            $table->decimal('min_amount', 12, 2)->nullable();
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->timestamp('last_executed')->nullable();
            $table->unsignedInteger('execution_time_ms')->default(0);
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_queries');
    }
};