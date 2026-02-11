<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcon_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->foreignId('gci_part_id')->constrained('gci_parts');
            $table->foreignId('bom_item_id')->nullable()->constrained('bom_items')->nullOnDelete();
            $table->string('process_type', 50); // plating, hardening, etc
            $table->decimal('qty_sent', 20, 4);
            $table->decimal('qty_received', 20, 4)->default(0);
            $table->decimal('qty_rejected', 20, 4)->default(0);
            $table->date('sent_date');
            $table->date('expected_return_date')->nullable();
            $table->date('received_date')->nullable();
            $table->string('status', 20)->default('draft'); // draft, sent, partial, completed, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index('gci_part_id');
            $table->index('sent_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcon_orders');
    }
};
