<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_unit_totals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('percentage', 12, 6);
            $table->decimal('contribution', 12, 2);
            $table->boolean('is_balancing_adjustment')->default(false);
            $table->timestamps();

            $table->unique(['budget_id', 'schedule_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_unit_totals');
    }
};
