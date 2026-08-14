<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('export_format', 10);
            $table->foreignId('batch_id')->nullable()->constrained('poultry_batches')->nullOnDelete();
            $table->string('export_type', 50);
            $table->string('file_name');
            $table->unsignedInteger('file_size')->nullable();
            $table->timestamp('exported_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_histories');
    }
};