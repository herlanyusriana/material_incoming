<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gci_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('on_hand', 15, 3)->default(0);
            $table->decimal('on_order', 15, 3)->default(0);
            $table->date('as_of_date')->nullable();
            $table->timestamps();

            $table->unique('gci_part_id');
            $table->index('gci_part_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gci_inventories');
    }
};

