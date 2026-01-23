<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('bom_item_substitutes')) {
            return;
        }

        Schema::create('bom_item_substitutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_item_id')->constrained('bom_items')->onDelete('cascade');
            $table->foreignId('substitute_part_id')->nullable()->constrained('gci_parts');
            // Add substitute_part_no just in case GCI part is missing but we have number
            $table->string('substitute_part_no')->nullable(); 
            $table->decimal('ratio', 10, 4)->default(1);
            $table->integer('priority')->default(1);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_item_substitutes');
    }
};
