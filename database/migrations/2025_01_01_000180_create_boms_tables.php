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
        if (!Schema::hasTable('boms')) {
            Schema::create('boms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('part_id')->constrained('parts')->onDelete('cascade');
                $table->string('bom_no')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bom_items')) {
            Schema::create('bom_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bom_id')->constrained('boms')->onDelete('cascade');
                $table->foreignId('component_part_id')->constrained('parts')->onDelete('cascade');
                $table->decimal('usage_qty', 18, 4);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('boms');
    }
};
