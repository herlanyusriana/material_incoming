<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_consumed_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();
            $table->string('location_code', 20);
            $table->string('batch_no', 50)->nullable();
            $table->decimal('qty_consumed', 15, 4);
            $table->foreignId('source_receive_id')->nullable()->constrained('incoming_receives')->nullOnDelete();
            $table->foreignId('source_arrival_id')->nullable()->constrained('incoming_arrivals')->nullOnDelete();
            $table->string('source_invoice_no', 100)->nullable();
            $table->foreignId('inventory_stock_movement_id')->nullable()
                ->constrained('inventory_stock_movements', indexName: 'prod_consumed_mat_inv_stock_mov_fk')
                ->nullOnDelete();
            $table->timestamp('consumed_at');
            $table->foreignId('consumed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['production_order_id', 'gci_part_id'], 'prod_consumed_mat_po_part_idx');
            $table->index('source_receive_id', 'prod_consumed_mat_rcv_idx');
            $table->index('source_arrival_id', 'prod_consumed_mat_arr_idx');
            $table->index('batch_no', 'prod_consumed_mat_batch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_consumed_materials');
    }
};
