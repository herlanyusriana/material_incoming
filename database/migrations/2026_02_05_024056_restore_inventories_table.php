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
        // 1. Restore inventories (Standard Part Inventory)
        if (!Schema::hasTable('inventories')) {
            Schema::create('inventories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('part_id')->unique()->constrained('parts')->onDelete('cascade');
                $table->decimal('on_hand', 20, 3)->default(0);
                $table->decimal('on_order', 20, 3)->default(0);
                $table->decimal('allocated', 20, 3)->default(0);
                $table->date('as_of_date')->nullable();
                $table->timestamps();
            });
        }

        // 2. Restore fg_inventory (Finished Goods Inventory)
        if (!Schema::hasTable('fg_inventory')) {
            Schema::create('fg_inventory', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gci_part_id')->unique()->constrained('gci_parts')->onDelete('cascade');
                $table->decimal('qty_on_hand', 20, 4)->default(0);
                $table->string('location')->nullable();
                $table->timestamps();
            });
        }

        // 3. Ensure gci_inventories exists (Internal Part Summary)
        if (!Schema::hasTable('gci_inventories')) {
            Schema::create('gci_inventories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gci_part_id')->unique()->constrained('gci_parts')->onDelete('cascade');
                $table->decimal('on_hand', 20, 4)->default(0);
                $table->decimal('on_order', 20, 4)->default(0);
                $table->date('as_of_date')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Safety: only drop if we really want to go back to unified-only state
        // Schema::dropIfExists('fg_inventory');
        // Schema::dropIfExists('gci_inventories');
        // Schema::dropIfExists('inventories');
    }
};
