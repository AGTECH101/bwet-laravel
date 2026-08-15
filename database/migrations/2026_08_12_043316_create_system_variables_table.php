<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_variables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key');
            $table->string('category', 20)->default('general');
            $table->string('value');
            $table->string('data_type', 20)->default('decimal');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('effective_from')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_variables');
    }
};