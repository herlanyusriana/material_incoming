<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('boms')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('component_part_id')->constrained('parts')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('usage_qty', 15, 3)->default(1);
            $table->timestamps();

            $table->unique(['bom_id', 'component_part_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_items');
    }
};
