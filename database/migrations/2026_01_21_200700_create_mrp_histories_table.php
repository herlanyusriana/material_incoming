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
        Schema::create('mrp_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mrp_run_id')->constrained('mrp_runs')->onDelete('cascade');
            $table->foreignId('part_id')->constrained('gci_parts')->onDelete('cascade');
            $table->date('plan_date');
            $table->string('plan_type'); // 'purchase' or 'production'
            $table->decimal('qty_before', 20, 4);
            $table->decimal('qty_after', 20, 4);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('mrp_run_id');
            $table->index('part_id');
            $table->index('plan_date');
            $table->index('plan_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mrp_histories');
    }
};
