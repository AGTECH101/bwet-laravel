<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('batch_state_migrations')) {
            Schema::create('batch_state_migrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('source_batch_id')->constrained('poultry_batches')->cascadeOnDelete();
                $table->foreignId('destination_batch_id')->nullable()->constrained('poultry_batches')->nullOnDelete();
                $table->enum('migration_type', [
                    'feed', 'expense', 'mortality', 'cull', 'slaughter',
                    'transfer_out', 'transfer_in', 'weight_gain'
                ]);
                $table->integer('count_moved')->default(0);
                $table->decimal('weight_moved', 12, 3)->default(0);
                $table->decimal('cost_moved', 14, 2)->default(0);
                $table->json('source_state_before')->nullable();
                $table->json('destination_state_before')->nullable();
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('source_batch_id');
                $table->index('destination_batch_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_state_migrations');
    }
};