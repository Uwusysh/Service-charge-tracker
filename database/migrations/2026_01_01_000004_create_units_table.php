<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('unit_reference');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('active_from')->nullable();
            $table->date('active_to')->nullable();
            $table->timestamps();

            $table->unique(['building_id', 'unit_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
