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
        if (!Schema::hasTable('forecasts')) {
            Schema::create('forecasts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('part_id')->constrained('gci_parts')->onDelete('cascade');
                $table->string('period', 7); // Monthly period: YYYY-MM (e.g., "2026-01")
                $table->decimal('qty', 20, 3)->default(0);
                $table->decimal('planning_qty', 20, 3)->default(0);
                $table->decimal('po_qty', 20, 3)->default(0);
                $table->string('source')->nullable(); // 'manual', 'import', 'customer'
                $table->timestamps();

                // Indexes
                $table->index('part_id');
                $table->index('period');
                $table->unique(['part_id', 'period']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecasts');
    }
};
