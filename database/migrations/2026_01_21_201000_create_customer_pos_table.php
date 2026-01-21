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
        Schema::create('customer_pos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('part_id')->nullable()->constrained('gci_parts')->onDelete('cascade');
            $table->string('po_no')->nullable(); // Changed from po_number to po_no to match models
            $table->string('period', 7); // YYYY-MM or YYYY-WW (renamed from minggu)
            $table->decimal('qty', 15, 3)->default(0);
            $table->string('status', 20)->default('open');
            $table->text('notes')->nullable();

            // Legacy/Extra fields from new migration
            $table->date('po_date')->nullable();
            $table->date('delivery_date')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('customer_id');
            $table->index('part_id');
            $table->index('po_no');
            $table->index('period');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_pos');
    }
};
