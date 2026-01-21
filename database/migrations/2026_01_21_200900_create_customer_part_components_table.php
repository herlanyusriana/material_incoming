<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_part_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_part_id')->constrained('customer_parts')->onDelete('cascade');
            $table->foreignId('gci_part_id')->constrained('gci_parts')->onDelete('cascade');
            $table->decimal('qty_per_unit', 20, 4)->default(1);
            $table->timestamps();

            // Indexes
            $table->index('customer_part_id');
            $table->index('gci_part_id');
            $table->unique(['customer_part_id', 'gci_part_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_part_components');
    }
};
