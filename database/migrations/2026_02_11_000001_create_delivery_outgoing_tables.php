<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create delivery_notes table
        if (!Schema::hasTable('delivery_notes')) {
            Schema::create('delivery_notes', function (Blueprint $table) {
                $table->id();
                $table->string('delivery_no')->unique();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('truck_id')->nullable();
                $table->enum('status', ['prepared', 'assigned', 'in_transit', 'delivered', 'cancelled'])->default('prepared');
                $table->text('notes')->nullable();
                $table->date('delivery_date')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable(); // Foreign key to users
                $table->decimal('total_value', 15, 2)->default(0);
                $table->timestamps();

                // Foreign keys
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
                $table->foreign('truck_id')->references('id')->on('trucking_companies')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Create delivery_items table
        if (!Schema::hasTable('delivery_items')) {
            Schema::create('delivery_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('delivery_note_id');
                $table->unsignedBigInteger('sales_order_id')->nullable(); // May not always link to SO
                $table->unsignedBigInteger('part_id');
                $table->decimal('quantity', 10, 2);
                $table->string('unit')->default('PCS');
                $table->text('notes')->nullable();
                $table->timestamps();

                // Foreign keys
                $table->foreign('delivery_note_id')->references('id')->on('delivery_notes')->onDelete('cascade');
                $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('set null');
                $table->foreign('part_id')->references('id')->on('gci_parts')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
        Schema::dropIfExists('delivery_notes');
    }
};