<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cost_heading_id')->constrained()->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->decimal('previous_budget', 12, 2)->nullable();
            $table->decimal('previous_actual', 12, 2)->nullable();
            $table->decimal('current_estimate', 12, 2);
            $table->boolean('is_reserve')->default(false);
            $table->text('basis_explanation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};
