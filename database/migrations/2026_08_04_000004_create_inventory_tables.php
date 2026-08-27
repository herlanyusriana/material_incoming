<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // INVENTORY LOCATION STOCK — SINGLE SOURCE OF TRUTH
        // ──────────────────────────────────────────────
        Schema::create('inventory_location_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

            $table->string('location_code');
            $table->string('batch_no')->nullable();
            $table->date('production_date')->nullable();

            $table->decimal('qty_on_hand', 20, 4)->default(0);

            // Tracking
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamp('last_movement_at')->nullable();

            // Unique: one part + location + batch
            $table->unique(['gci_part_id', 'location_code', 'batch_no'], 'inv_loc_stock_uniq');

            $table->softDeletes();
            $table->timestamps();

            $table->index('gci_part_id');
            $table->index('location_code');
            $table->index('batch_no');
            $table->index('qty_on_hand');
        });

        // ──────────────────────────────────────────────
        // INVENTORY STOCK MOVEMENTS (Audit Log)
        // Legacy table created by 2026_04_16 migration wins if present
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('inventory_stock_movements')) {
        Schema::create('inventory_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

            $table->string('location_code');
            $table->string('batch_no')->nullable();

            // Before/after
            $table->decimal('qty_before', 20, 4);
            $table->decimal('qty_change', 20, 4); // positive = in, negative = out
            $table->decimal('qty_after', 20, 4);

            // Context
            $table->string('transaction_type'); // receive, consume, transfer, adjustment, etc.
            $table->string('source_reference')->nullable(); // e.g. ARR-2026-0001, WO1234

            // Traceability
            $table->foreignId('source_receive_id')->nullable()->constrained('incoming_receives')->nullOnDelete();
            $table->foreignId('source_arrival_id')->nullable()->constrained('incoming_arrivals')->nullOnDelete();
            $table->string('source_invoice_no')->nullable();
            $table->string('source_delivery_note_no')->nullable();

            // Weight
            $table->decimal('weight_kgm', 20, 4)->nullable();

            // User
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Timestamp of the movement
            $table->timestamp('movement_at');

            $table->softDeletes();
            $table->timestamps();

            $table->index('gci_part_id');
            $table->index('location_code');
            $table->index('batch_no');
            $table->index('transaction_type');
            $table->index('source_reference');
            $table->index('movement_at');
        });
        }

        // ──────────────────────────────────────────────
        // INVENTORY BIN TRANSFERS
        // ──────────────────────────────────────────────
        Schema::create('inventory_bin_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no')->unique();

            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

            $table->string('from_location_code');
            $table->string('to_location_code');

            $table->string('batch_no')->nullable();
            $table->decimal('qty_transferred', 20, 4);

            $table->string('status')->default('pending'); // pending | completed | cancelled

            $table->string('transfer_type')->nullable(); // manual, replenishment, etc.

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('gci_part_id');
            $table->index('from_location_code');
            $table->index('to_location_code');
            $table->index('status');
            $table->index('transfer_no');
        });

        // ──────────────────────────────────────────────
        // INVENTORY SUPPLIES (Production → WH)
        // Legacy table created by 2026_04_16 migration wins if present
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('inventory_supplies')) {
        Schema::create('inventory_supplies', function (Blueprint $table) {
            $table->id();
            $table->string('supply_no')->unique();

            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

            $table->decimal('qty_supplied', 20, 4);
            $table->string('unit', 20)->nullable();

            $table->string('to_location_code');

            $table->foreignId('supplied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supplied_at');

            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('production_order_id');
            $table->index('gci_part_id');
            $table->index('to_location_code');
            $table->index('supply_no');
        });
        }

        // ──────────────────────────────────────────────
        // INVENTORY RETURNS (Production → WH)
        // Legacy table created by 2026_04_16 migration wins if present
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('inventory_returns')) {
        Schema::create('inventory_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique();

            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

            $table->decimal('qty_returned', 20, 4);
            $table->string('unit', 20)->nullable();

            $table->string('from_location_code');
            $table->string('to_location_code')->nullable();

            $table->string('reason')->nullable();

            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at');

            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('production_order_id');
            $table->index('gci_part_id');
            $table->index('from_location_code');
            $table->index('return_no');
        });
        }

        // ──────────────────────────────────────────────
        // INVENTORY STOCK OPNAME SESSIONS
        // ──────────────────────────────────────────────
        Schema::create('inventory_stock_opname_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('opname_no')->unique();

            $table->string('location_code')->nullable();
            $table->date('opname_date');

            $table->string('status')->default('draft'); // draft | in_progress | completed | cancelled

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('location_code');
            $table->index('status');
            $table->index('opname_no');
        });

        // ──────────────────────────────────────────────
        // INVENTORY STOCK OPNAME ITEMS
        // ──────────────────────────────────────────────
        Schema::create('inventory_stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opname_session_id')->constrained('inventory_stock_opname_sessions')->cascadeOnDelete();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

            $table->string('location_code');
            $table->string('batch_no')->nullable();

            $table->decimal('system_qty', 20, 4);
            $table->decimal('actual_qty', 20, 4);
            $table->decimal('difference', 20, 4)->storedAs('actual_qty - system_qty');

            $table->text('notes')->nullable();

            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('opname_session_id');
            $table->index('gci_part_id');
            $table->index('location_code');
        });

        // ──────────────────────────────────────────────
        // FG INVENTORY (Finished Goods summary — view recommended, but table kept for performance)
        // ──────────────────────────────────────────────
        Schema::create('inventory_fg_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gci_part_id')->unique()->constrained('gci_parts')->cascadeOnDelete();

            $table->decimal('qty_on_hand', 20, 4)->default(0);
            $table->string('location_code')->nullable();

            $table->timestamp('last_updated_at');

            $table->softDeletes();
            $table->timestamps();

            $table->index('gci_part_id');
            $table->index('location_code');
        });

        // ──────────────────────────────────────────────
        // STOCK AT CUSTOMERS
        // ──────────────────────────────────────────────
        Schema::create('inventory_stock_at_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            $table->date('as_of_date');
            $table->decimal('qty', 20, 4);
            $table->string('location')->nullable();

            $table->text('notes')->nullable();

            $table->unique(['gci_part_id', 'customer_id', 'as_of_date'], 'inv_stock_cust_uniq');

            $table->softDeletes();
            $table->timestamps();

            $table->index('gci_part_id');
            $table->index('customer_id');
            $table->index('as_of_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_at_customers');
        Schema::dropIfExists('inventory_fg_stock');
        Schema::dropIfExists('inventory_stock_opname_items');
        Schema::dropIfExists('inventory_stock_opname_sessions');
        Schema::dropIfExists('inventory_returns');
        Schema::dropIfExists('inventory_supplies');
        Schema::dropIfExists('inventory_bin_transfers');
        Schema::dropIfExists('inventory_stock_movements');
        Schema::dropIfExists('inventory_location_stock');
    }
};