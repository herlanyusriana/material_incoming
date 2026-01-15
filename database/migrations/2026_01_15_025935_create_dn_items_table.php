<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dn_id')->constrained('delivery_notes')->cascadeOnDelete();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('customer_po_id')->nullable()->constrained('customer_pos')->nullOnDelete();
            $table->decimal('qty', 18, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dn_items');
    }
};
