<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // TRUCKING COMPANIES
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('trucking_companies')) {
            Schema::create('trucking_companies', function (Blueprint $table) {
                $table->id();
                $table->string('company_code')->unique();
                $table->string('company_name');
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('status')->default('active');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // ──────────────────────────────────────────────
        // TRUCKS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('trucks')) {
            Schema::create('trucks', function (Blueprint $table) {
                $table->id();
                $table->string('license_plate')->unique();
                $table->string('truck_type')->nullable();
                $table->string('capacity')->nullable();
                $table->foreignId('trucking_company_id')->nullable()->constrained('trucking_companies')->nullOnDelete();
                $table->string('status')->default('active');
                $table->softDeletes();
                $table->timestamps();

                $table->index('license_plate');
            });
        }

        // ──────────────────────────────────────────────
        // DRIVERS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('drivers')) {
            Schema::create('drivers', function (Blueprint $table) {
                $table->id();
                $table->string('driver_code')->unique();
                $table->string('driver_name');
                $table->string('license_number')->nullable();
                $table->string('phone')->nullable();
                $table->foreignId('trucking_company_id')->nullable()->constrained('trucking_companies')->nullOnDelete();
                $table->string('status')->default('active');
                $table->softDeletes();
                $table->timestamps();

                $table->index('driver_code');
            });
        }

        // ──────────────────────────────────────────────
        // SALES ORDERS (Customer PO)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('sales_orders')) {
            Schema::create('sales_orders', function (Blueprint $table) {
                $table->id();
                $table->string('so_no')->unique();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

                $table->date('order_date');
                $table->date('delivery_date')->nullable();

                $table->string('po_customer_no')->nullable(); // Customer's PO number
                $table->string('status')->default('draft'); // draft | confirmed | in_progress | completed | cancelled

                $table->text('notes')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index('so_no');
                $table->index('customer_id');
                $table->index('status');
            });
        }

        // ──────────────────────────────────────────────
        // SALES ORDER ITEMS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('sales_order_items')) {
            Schema::create('sales_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                $table->decimal('qty_ordered', 20, 4);
                $table->decimal('qty_delivered', 20, 4)->default(0);
                $table->decimal('unit_price', 20, 4)->nullable();
                $table->string('unit', 20)->nullable();

                $table->date('required_date')->nullable();

                $table->text('notes')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('sales_order_id');
                $table->index('gci_part_id');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING DAILY PLANS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_daily_plans')) {
            Schema::create('outgoing_daily_plans', function (Blueprint $table) {
                $table->id();
                $table->date('date_from');
                $table->date('date_to')->nullable();

                $table->string('status')->default('draft'); // draft | active | completed

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index('date_from');
                $table->index('status');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING DAILY PLAN ROWS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_daily_plan_rows')) {
            Schema::create('outgoing_daily_plan_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained('outgoing_daily_plans')->cascadeOnDelete();

                $table->integer('row_no')->nullable();

                // Part being planned
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();
                $table->foreignId('customer_part_id')->nullable()->constrained('customer_parts')->nullOnDelete();

                // Quantities per day (stored as JSON for flexibility)
                $table->json('daily_quantities')->nullable(); // { "2026-08-01": 100, "2026-08-02": 150 }

                // Total
                $table->decimal('total_qty', 20, 4)->default(0);

                // Reference
                $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();

                $table->text('notes')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('plan_id');
                $table->index('gci_part_id');
                $table->index('row_no');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING DAILY PLAN CELLS (per day)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_daily_plan_cells')) {
            Schema::create('outgoing_daily_plan_cells', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_row_id')->constrained('outgoing_daily_plan_rows')->cascadeOnDelete();

                $table->date('date');
                $table->decimal('qty', 20, 4)->default(0);

                // Production order reference (when this cell is released to production)
                $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();

                // Status of this cell
                $table->string('status')->default('pending'); // pending | released | in_progress | completed

                $table->softDeletes();
                $table->timestamps();

                $table->index('plan_row_id');
                $table->index('date');
                $table->index('production_order_id');
                $table->index('status');

                $table->unique(['plan_row_id', 'date']);
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING DELIVERY PLANNING LINES
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_delivery_planning_lines')) {
            Schema::create('outgoing_delivery_planning_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('daily_plan_cell_id')->nullable()->constrained('outgoing_daily_plan_cells')->nullOnDelete();
                $table->foreignId('sales_order_item_id')->nullable()->constrained('sales_order_items')->nullOnDelete();

                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                $table->date('planned_date');
                $table->decimal('qty_planned', 20, 4);

                $table->decimal('qty_picked', 20, 4)->default(0);
                $table->decimal('qty_delivered', 20, 4)->default(0);

                $table->string('status')->default('planned'); // planned | picked | delivered | cancelled

                // Source
                $table->string('source_type')->nullable(); // sales_order, forecast, etc.
                $table->string('source_reference')->nullable();

                $table->text('notes')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('daily_plan_cell_id');
                $table->index('sales_order_item_id');
                $table->index('gci_part_id');
                $table->index('planned_date');
                $table->index('status');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING PICKING FG (Finished Goods Picking)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_picking_fg')) {
            Schema::create('outgoing_picking_fg', function (Blueprint $table) {
                $table->id();
                $table->string('picking_no')->unique();

                $table->foreignId('delivery_planning_line_id')->nullable()->constrained('outgoing_delivery_planning_lines')->nullOnDelete();
                $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();

                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                $table->decimal('qty_picked', 20, 4);
                $table->string('unit', 20)->nullable();

                $table->string('from_location_code');
                $table->string('batch_no')->nullable();

                $table->string('status')->default('draft'); // draft | picked | transferred

                $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('picked_at')->nullable();

                $table->text('notes')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('picking_no');
                $table->index('gci_part_id');
                $table->index('from_location_code');
                $table->index('status');
                $table->index('delivery_planning_line_id');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING DELIVERY NOTES
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_delivery_notes')) {
            Schema::create('outgoing_delivery_notes', function (Blueprint $table) {
                $table->id();
                $table->string('dn_no')->unique();
                $table->string('transaction_no')->nullable()->unique();

                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

                $table->date('delivery_date');
                $table->date('planned_delivery_date')->nullable();

                // Driver & truck
                $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
                $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete();

                // Status
                $table->string('status')->default('draft'); // draft | picked | loaded | in_transit | delivered | cancelled

                // Documents
                $table->string('invoice_file')->nullable();
                $table->string('packing_list_file')->nullable();

                $table->text('notes')->nullable();
                $table->text('delivery_address')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index('dn_no');
                $table->index('customer_id');
                $table->index('delivery_date');
                $table->index('status');
                $table->index('driver_id');
                $table->index('truck_id');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING DELIVERY NOTE ITEMS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_delivery_note_items')) {
            Schema::create('outgoing_delivery_note_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('delivery_note_id')->constrained('outgoing_delivery_notes')->cascadeOnDelete();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                $table->decimal('qty_delivered', 20, 4);
                $table->string('unit', 20)->nullable();

                // References
                $table->foreignId('sales_order_item_id')->nullable()->constrained('sales_order_items')->nullOnDelete();
                $table->foreignId('picking_fg_id')->nullable()->constrained('outgoing_picking_fg')->nullOnDelete();

                // Batch / location
                $table->string('batch_no')->nullable();
                $table->string('from_location_code')->nullable();

                // Price
                $table->decimal('unit_price', 20, 4)->nullable();
                $table->decimal('total_price', 20, 4)->nullable();

                $table->text('notes')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('delivery_note_id');
                $table->index('gci_part_id');
                $table->index('sales_order_item_id');
                $table->index('batch_no');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING DELIVERY ORDERS (alternative to notes)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_delivery_orders')) {
            Schema::create('outgoing_delivery_orders', function (Blueprint $table) {
                $table->id();
                $table->string('do_no')->unique();
                $table->foreignId('delivery_note_id')->nullable()->constrained('outgoing_delivery_notes')->nullOnDelete();

                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

                $table->date('order_date');
                $table->date('delivery_date')->nullable();

                $table->string('status')->default('draft'); // draft | confirmed | in_progress | delivered | cancelled

                $table->text('notes')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index('do_no');
                $table->index('customer_id');
                $table->index('status');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING DELIVERY ORDER ITEMS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_delivery_order_items')) {
            Schema::create('outgoing_delivery_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('delivery_order_id')->constrained('outgoing_delivery_orders')->cascadeOnDelete();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                $table->decimal('qty_ordered', 20, 4);
                $table->decimal('qty_delivered', 20, 4)->default(0);
                $table->string('unit', 20)->nullable();

                $table->decimal('unit_price', 20, 4)->nullable();

                $table->text('notes')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('delivery_order_id');
                $table->index('gci_part_id');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING PO (Production Order? or Purchase Order?)
        // ──────────────────────────────────────────────
        // This seems to be Outgoing Production Order based on model
        if (!Schema::hasTable('outgoing_pos')) {
            Schema::create('outgoing_pos', function (Blueprint $table) {
                $table->id();
                $table->string('po_no')->unique();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

                $table->date('order_date');
                $table->date('delivery_date')->nullable();

                $table->string('status')->default('draft'); // draft | confirmed | in_production | completed | cancelled

                $table->text('notes')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index('po_no');
                $table->index('customer_id');
                $table->index('status');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING PO ITEMS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_po_items')) {
            Schema::create('outgoing_po_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outgoing_po_id')->constrained('outgoing_pos')->cascadeOnDelete();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                $table->decimal('qty_ordered', 20, 4);
                $table->decimal('qty_delivered', 20, 4)->default(0);
                $table->string('unit', 20)->nullable();

                $table->decimal('unit_price', 20, 4)->nullable();

                $table->text('notes')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('outgoing_po_id');
                $table->index('gci_part_id');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING DELIVERY REQUIREMENTS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_delivery_requirements')) {
            Schema::create('outgoing_delivery_requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_order_item_id')->constrained('sales_order_items')->cascadeOnDelete();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                $table->date('required_date');
                $table->decimal('qty_required', 20, 4);
                $table->decimal('qty_fulfilled', 20, 4)->default(0);

                $table->string('status')->default('pending'); // pending | fulfilled | partial | cancelled

                $table->text('notes')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('sales_order_item_id');
                $table->index('gci_part_id');
                $table->index('required_date');
                $table->index('status');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING DELIVERY REQUIREMENT FULFILLMENTS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_delivery_requirement_fulfillments')) {
            Schema::create('outgoing_delivery_requirement_fulfillments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('requirement_id');
                $table->foreign('requirement_id', 'odrf_requirement_fk')
                    ->references('id')->on('outgoing_delivery_requirements')
                    ->cascadeOnDelete();
                $table->unsignedBigInteger('delivery_note_item_id')->nullable();
                $table->foreign('delivery_note_item_id', 'odrf_dn_item_fk')
                    ->references('id')->on('outgoing_delivery_note_items')
                    ->nullOnDelete();

                $table->decimal('qty_fulfilled', 20, 4);

                $table->timestamps();

                $table->index('requirement_id', 'odrf_requirement_idx');
                $table->index('delivery_note_item_id', 'odrf_dn_item_idx');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING JIG SETTINGS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_jig_settings')) {
            Schema::create('outgoing_jig_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();
                $table->foreignId('customer_part_id')->nullable()->constrained('customer_parts')->nullOnDelete();

                $table->string('jig_code')->nullable();
                $table->string('jig_name')->nullable();
                $table->text('description')->nullable();

                $table->string('status')->default('active');

                $table->softDeletes();
                $table->timestamps();

                $table->index('gci_part_id');
                $table->index('jig_code');
            });
        }

        // ──────────────────────────────────────────────
        // OUTGOING JIG PLANS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('outgoing_jig_plans')) {
            Schema::create('outgoing_jig_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('jig_setting_id')->constrained('outgoing_jig_settings')->cascadeOnDelete();
                $table->foreignId('delivery_planning_line_id')->nullable()->constrained('outgoing_delivery_planning_lines')->nullOnDelete();

                $table->date('planned_date');
                $table->decimal('qty', 20, 4);

                $table->string('status')->default('planned'); // planned | in_progress | completed

                $table->softDeletes();
                $table->timestamps();

                $table->index('jig_setting_id');
                $table->index('delivery_planning_line_id');
                $table->index('planned_date');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_jig_plans');
        Schema::dropIfExists('outgoing_jig_settings');
        Schema::dropIfExists('outgoing_delivery_requirement_fulfillments');
        Schema::dropIfExists('outgoing_delivery_requirements');
        Schema::dropIfExists('outgoing_po_items');
        Schema::dropIfExists('outgoing_pos');
        Schema::dropIfExists('outgoing_delivery_order_items');
        Schema::dropIfExists('outgoing_delivery_orders');
        Schema::dropIfExists('outgoing_delivery_note_items');
        Schema::dropIfExists('outgoing_delivery_notes');
        Schema::dropIfExists('outgoing_picking_fg');
        Schema::dropIfExists('outgoing_delivery_planning_lines');
        Schema::dropIfExists('outgoing_daily_plan_cells');
        Schema::dropIfExists('outgoing_daily_plan_rows');
        Schema::dropIfExists('outgoing_daily_plans');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('trucks');
        Schema::dropIfExists('trucking_companies');
    }
};