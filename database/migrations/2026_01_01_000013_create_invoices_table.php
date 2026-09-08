<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_charge_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_line_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_reference');
            $table->string('normalized_reference');
            $table->date('invoice_date');
            $table->decimal('net_amount', 12, 2);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('gross_amount', 12, 2);
            $table->string('status')->default('draft'); // draft|submitted|approved|rejected
            $table->text('description')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('reverse_reason')->nullable();
            $table->timestamps();

            $table->index(['building_id', 'supplier_id', 'normalized_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
