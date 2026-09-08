<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_charge_year_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('status')->default('draft'); // draft|ready_for_review|final|archived
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('prepared_at')->nullable();
            $table->string('authorised_approver')->nullable();
            $table->timestamp('authorised_at')->nullable();
            $table->string('authorised_capacity')->nullable();
            $table->foreignId('finalised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalised_at')->nullable();
            $table->text('overall_notes')->nullable();
            $table->foreignId('copied_from_budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['building_id', 'service_charge_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
