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
        Schema::create('mrp_purchase_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mrp_run_id')->constrained('mrp_runs')->onDelete('cascade');
            $table->foreignId('part_id')->constrained('gci_parts')->onDelete('cascade');
            $table->date('plan_date');
            $table->decimal('net_required', 20, 4)->default(0);
            $table->decimal('planned_order_rec', 20, 4)->default(0);
            $table->timestamps();

            // Indexes
            $table->index('mrp_run_id');
            $table->index('part_id');
            $table->index('plan_date');
            $table->index(['part_id', 'plan_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mrp_purchase_plans');
    }
};
