<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom_item_substitutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_item_id')->constrained('bom_items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('substitute_part_id')->constrained('gci_parts')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('ratio', 15, 3)->default(1);
            $table->unsignedInteger('priority')->default(1);
            $table->string('status', 20)->default('active'); // active|inactive
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['bom_item_id', 'substitute_part_id']);
            $table->index(['bom_item_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_item_substitutes');
    }
};

