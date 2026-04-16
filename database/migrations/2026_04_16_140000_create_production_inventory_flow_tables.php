<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->string('type', 50)->default('production');
                $table->string('status', 20)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('production_inventories')) {
            Schema::create('production_inventories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
                $table->foreignId('machine_id')->nullable()->constrained('machines')->nullOnDelete();
                $table->string('code', 80)->unique();
                $table->string('name', 150);
                $table->string('inventory_type', 50)->default('line');
                $table->string('location_code', 50)->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_supplies')) {
            Schema::create('inventory_supplies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
                $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
                $table->foreignId('production_inventory_id')->constrained('production_inventories')->cascadeOnDelete();
                $table->foreignId('gci_part_id')->nullable()->constrained('gci_parts')->nullOnDelete();
                $table->unsignedBigInteger('part_id')->nullable();
                $table->string('tag_number', 100);
                $table->string('part_no', 100)->nullable();
                $table->string('part_name', 255)->nullable();
                $table->string('uom', 50)->nullable();
                $table->string('consumption_policy', 50)->default('backflush_return');
                $table->string('status', 30)->default('supplied');
                $table->string('source_location_code', 50)->nullable();
                $table->string('target_location_code', 50)->nullable();
                $table->decimal('qty_supply', 20, 4)->default(0);
                $table->decimal('qty_consumed', 20, 4)->default(0);
                $table->decimal('qty_returned', 20, 4)->default(0);
                $table->json('traceability')->nullable();
                $table->timestamp('supplied_at')->nullable();
                $table->foreignId('supplied_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['production_order_id', 'tag_number']);
                $table->unique(['production_order_id', 'tag_number'], 'inventory_supplies_order_tag_unique');
            });
        }

        if (!Schema::hasTable('inventory_returns')) {
            Schema::create('inventory_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_supply_id')->constrained('inventory_supplies')->cascadeOnDelete();
                $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
                $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
                $table->foreignId('production_inventory_id')->constrained('production_inventories')->cascadeOnDelete();
                $table->foreignId('gci_part_id')->nullable()->constrained('gci_parts')->nullOnDelete();
                $table->unsignedBigInteger('part_id')->nullable();
                $table->string('tag_number', 100);
                $table->string('uom', 50)->nullable();
                $table->string('from_location_code', 50)->nullable();
                $table->string('to_location_code', 50)->nullable();
                $table->decimal('qty_return', 20, 4)->default(0);
                $table->json('notes')->nullable();
                $table->timestamp('returned_at')->nullable();
                $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_stock_movements')) {
            Schema::create('inventory_stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();
                $table->foreignId('inventory_supply_id')->nullable()->constrained('inventory_supplies')->nullOnDelete();
                $table->foreignId('inventory_return_id')->nullable()->constrained('inventory_returns')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('production_inventory_id')->nullable()->constrained('production_inventories')->nullOnDelete();
                $table->foreignId('gci_part_id')->nullable()->constrained('gci_parts')->nullOnDelete();
                $table->unsignedBigInteger('part_id')->nullable();
                $table->string('tag_number', 100)->nullable();
                $table->string('part_no', 100)->nullable();
                $table->string('part_name', 255)->nullable();
                $table->string('movement_type', 50);
                $table->string('uom', 50)->nullable();
                $table->string('from_location_code', 50)->nullable();
                $table->string('to_location_code', 50)->nullable();
                $table->decimal('qty', 20, 4)->default(0);
                $table->json('notes')->nullable();
                $table->timestamp('moved_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['production_order_id', 'movement_type'], 'inv_stock_mv_order_type_idx');
                $table->index(['tag_number', 'movement_type'], 'inv_stock_mv_tag_type_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_movements');
        Schema::dropIfExists('inventory_returns');
        Schema::dropIfExists('inventory_supplies');
        Schema::dropIfExists('production_inventories');
        Schema::dropIfExists('departments');
    }
};
