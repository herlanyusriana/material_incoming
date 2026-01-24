<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('location_inventory_adjustments')) {
            return;
        }

        Schema::create('location_inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts')->cascadeOnDelete();
            $table->string('location_code', 50);
            $table->decimal('qty_before', 20, 4)->default(0);
            $table->decimal('qty_after', 20, 4)->default(0);
            $table->decimal('qty_change', 20, 4)->default(0);
            $table->string('reason', 1000)->nullable();
            $table->timestamp('adjusted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['location_code', 'part_id']);
            $table->index('adjusted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_inventory_adjustments');
    }
};

