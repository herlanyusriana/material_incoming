<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('outgoing_pos', function (Blueprint $table) {
            $table->id();
            $table->string('po_no')->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('po_release_date');
            $table->string('status', 20)->default('draft'); // draft, confirmed, completed, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index('po_release_date');
        });

        Schema::create('outgoing_po_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outgoing_po_id')->constrained('outgoing_pos')->cascadeOnDelete();
            $table->string('vendor_part_name'); // customer's part name
            $table->foreignId('gci_part_id')->nullable()->constrained('gci_parts')->nullOnDelete();
            $table->integer('qty')->default(0);
            $table->decimal('price', 15, 2)->default(0); // harga PO
            $table->date('delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('outgoing_po_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_po_items');
        Schema::dropIfExists('outgoing_pos');
    }
};
