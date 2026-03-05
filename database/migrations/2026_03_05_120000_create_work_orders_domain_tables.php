<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('work_orders')) {
            Schema::create('work_orders', function (Blueprint $table) {
                $table->id();
                $table->string('wo_no')->unique();
                $table->foreignId('fg_part_id')->constrained('gci_parts')->cascadeOnDelete();
                $table->decimal('qty_plan', 20, 4);
                $table->date('plan_date');
                $table->string('status')->default('open')->index(); // open|in_progress|qc|closed
                $table->unsignedTinyInteger('priority')->default(3)->index();
                $table->text('remarks')->nullable();

                $table->string('source_type', 32)->default('manual')->index(); // manual|mrp|outgoing_daily
                $table->string('source_ref_type')->nullable();
                $table->unsignedBigInteger('source_ref_id')->nullable();
                $table->json('source_payload_json')->nullable();

                $table->json('routing_json')->nullable();
                $table->json('schedule_json')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['source_ref_type', 'source_ref_id'], 'work_orders_source_ref_idx');
            });
        }

        if (!Schema::hasTable('work_order_bom_snapshots')) {
            Schema::create('work_order_bom_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
                $table->foreignId('bom_id')->nullable()->constrained('boms')->nullOnDelete();
                $table->foreignId('bom_item_id')->nullable()->constrained('bom_items')->nullOnDelete();
                $table->unsignedInteger('line_no')->default(0);

                $table->foreignId('component_part_id')->nullable()->constrained('gci_parts')->nullOnDelete();
                $table->string('component_part_no')->nullable();
                $table->string('component_part_name')->nullable();
                $table->decimal('usage_qty', 20, 6)->default(0);
                $table->decimal('scrap_factor', 12, 6)->default(0);
                $table->decimal('yield_factor', 12, 6)->default(1);
                $table->decimal('net_required_per_fg', 20, 6)->default(0);
                $table->string('consumption_uom')->nullable();
                $table->string('process_name')->nullable();
                $table->string('machine_name')->nullable();
                $table->string('material_name')->nullable();
                $table->string('material_spec')->nullable();
                $table->string('material_size')->nullable();
                $table->string('make_or_buy', 16)->nullable();
                $table->json('substitutes_json')->nullable();
                $table->timestamps();

                $table->index(['work_order_id', 'line_no'], 'wo_bom_snap_line_idx');
            });
        }

        if (!Schema::hasTable('work_order_requirement_snapshots')) {
            Schema::create('work_order_requirement_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
                $table->foreignId('component_part_id')->nullable()->constrained('gci_parts')->nullOnDelete();
                $table->string('component_part_no')->nullable();
                $table->string('component_part_name')->nullable();
                $table->string('uom')->nullable();
                $table->decimal('qty_per_fg', 20, 6)->default(0);
                $table->decimal('qty_requirement', 20, 6)->default(0);
                $table->timestamps();

                $table->index(['work_order_id', 'component_part_id'], 'wo_req_snap_part_idx');
            });
        }

        if (!Schema::hasTable('work_order_histories')) {
            Schema::create('work_order_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
                $table->string('event_type', 64)->index(); // created|updated|status_changed|...
                $table->json('before_json')->nullable();
                $table->json('after_json')->nullable();
                $table->text('remarks')->nullable();
                $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_histories');
        Schema::dropIfExists('work_order_requirement_snapshots');
        Schema::dropIfExists('work_order_bom_snapshots');
        Schema::dropIfExists('work_orders');
    }
};

