<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_part_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_part_id')->constrained('customer_parts')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('gci_parts')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('usage_qty', 15, 3)->default(1);
            $table->timestamps();

            $table->unique(['customer_part_id', 'part_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_part_components');
    }
};
