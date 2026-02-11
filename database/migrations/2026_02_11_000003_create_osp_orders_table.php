<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osp_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('gci_part_id')->constrained('gci_parts');
            $table->foreignId('bom_item_id')->nullable()->constrained('bom_items')->nullOnDelete();
            $table->decimal('qty_received_material', 20, 4);
            $table->decimal('qty_assembled', 20, 4)->default(0);
            $table->decimal('qty_shipped', 20, 4)->default(0);
            $table->date('received_date');
            $table->date('target_ship_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->string('status', 20)->default('received'); // received, in_progress, ready, shipped, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index('gci_part_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osp_orders');
    }
};
