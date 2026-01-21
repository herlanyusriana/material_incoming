<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_pos', function (Blueprint $table) {
            $table->id();
            $table->string('po_no', 100)->nullable();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('customer_part_no', 100)->nullable();
            $table->foreignId('part_id')->nullable()->constrained('gci_parts')->nullOnDelete()->cascadeOnUpdate();
            $table->string('minggu', 8);
            $table->decimal('qty', 15, 3)->default(0);
            $table->string('status', 20)->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['minggu', 'status']);
            $table->index('customer_part_no');
            $table->index('po_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_pos');
    }
};
