<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fg_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('qty_on_hand', 18, 4)->default(0);
            $table->string('location')->nullable();
            $table->timestamps();
            
            $table->unique('gci_part_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fg_inventory');
    }
};
