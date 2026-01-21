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
        // 1. GCI Inventories
        if (!Schema::hasTable('gci_inventories')) {
            Schema::create('gci_inventories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gci_part_id')->unique()->constrained('gci_parts')->onDelete('cascade');
                $table->decimal('on_hand', 20, 4)->default(0);
                $table->decimal('on_order', 20, 4)->default(0);
                $table->date('as_of_date')->nullable();
                $table->timestamps();
            });
        }

        // 2. FG Inventories
        if (!Schema::hasTable('fg_inventory')) {
            Schema::create('fg_inventory', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gci_part_id')->unique()->constrained('gci_parts')->onDelete('cascade');
                $table->decimal('qty_on_hand', 20, 4)->default(0);
                $table->string('location')->nullable();
                $table->timestamps();
            });
        }

        // 3. Production Orders
        if (!Schema::hasTable('production_orders')) {
            Schema::create('production_orders', function (Blueprint $table) {
                $table->id();
                $table->string('production_order_number')->unique();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->onDelete('cascade');
                $table->date('plan_date');
                $table->decimal('qty_planned', 18, 4);
                $table->decimal('qty_actual', 18, 4)->default(0);
                $table->string('status')->default('draft');
                $table->string('workflow_stage')->nullable();
                $table->dateTime('start_time')->nullable();
                $table->dateTime('end_time')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // 4. Delivery Notes
        if (!Schema::hasTable('delivery_notes')) {
            Schema::create('delivery_notes', function (Blueprint $table) {
                $table->id();
                $table->string('dn_no')->unique();
                $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
                $table->date('delivery_date');
                $table->string('status')->default('draft');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 5. DN Items
        if (!Schema::hasTable('dn_items')) {
            Schema::create('dn_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dn_id')->constrained('delivery_notes')->onDelete('cascade');
                $table->foreignId('gci_part_id')->constrained('gci_parts')->onDelete('cascade');
                $table->decimal('qty', 18, 4);
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dn_items');
        Schema::dropIfExists('delivery_notes');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('fg_inventory');
        Schema::dropIfExists('gci_inventories');
    }
};
