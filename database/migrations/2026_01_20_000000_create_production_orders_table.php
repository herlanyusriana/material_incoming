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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('production_order_number')->unique();
            $table->foreignId('gci_part_id')->constrained('gci_parts');
            $table->foreignId('mps_id')->nullable()->constrained('mps'); // Optional link to MPS
            $table->date('plan_date');
            $table->decimal('qty_planned', 10, 2);
            $table->decimal('qty_actual', 10, 2)->default(0);
            $table->decimal('qty_rejected', 10, 2)->default(0);
            
            // Status based on the flow:
            // Planned -> Material Check (Pass/Fail) -> Start -> Inspection(1st) -> Mass Prod -> Inspection(IP) -> Finish -> Inspection(Final) -> Done
            $table->enum('status', [
                'planned', 
                'released',        // Material Check OK, Ready for floor
                'material_hold',   // Material Check Failed
                'in_production',   // Process started
                'completed',       // All good
                'cancelled'
            ])->default('planned');

            // Granular status to track where exactly we are in the flow
            $table->string('workflow_stage')->default('created'); 
            // created, material_check, ready, first_article_inspection, mass_production, in_process_inspection, finished, final_inspection, stock_update

            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
