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
        Schema::create('contract_number_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_number_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gci_part_id')->constrained();
            $table->foreignId('rm_gci_part_id')->constrained('gci_parts');
            $table->foreignId('bom_item_id')->nullable()->constrained('bom_items')->nullOnDelete();
            $table->string('process_type', 50);
            $table->decimal('target_qty', 12, 4);
            $table->decimal('warning_limit_qty', 12, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_number_items');
    }
};
