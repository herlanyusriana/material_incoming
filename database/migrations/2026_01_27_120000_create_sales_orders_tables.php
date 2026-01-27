<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_orders')) {
            Schema::create('sales_orders', function (Blueprint $table) {
                $table->id();
                $table->string('so_no')->unique();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->date('so_date')->index();
                $table->string('status')->default('draft'); // draft, assigned, partial_shipped, shipped
                $table->text('notes')->nullable();

                $table->foreignId('delivery_plan_id')->nullable()->constrained('delivery_plans')->nullOnDelete();
                $table->foreignId('delivery_stop_id')->nullable()->constrained('delivery_stops')->nullOnDelete();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['so_date', 'customer_id']);
            });
        }

        if (!Schema::hasTable('sales_order_items')) {
            Schema::create('sales_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();
                $table->decimal('qty_ordered', 18, 4);
                $table->decimal('qty_shipped', 18, 4)->default(0);
                $table->timestamps();

                $table->unique(['sales_order_id', 'gci_part_id']);
                $table->index(['gci_part_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};

