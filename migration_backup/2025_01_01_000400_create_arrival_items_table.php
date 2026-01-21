<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrival_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrival_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('part_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('qty_bundle');
            $table->integer('qty_600ds');
            $table->decimal('weight_nett', 10, 2);
            $table->decimal('weight_gross', 10, 2);
            $table->decimal('price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrival_items');
    }
};
