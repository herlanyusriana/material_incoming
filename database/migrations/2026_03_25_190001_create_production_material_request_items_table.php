<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_material_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_material_request_id');
            $table->unsignedBigInteger('part_id');
            $table->string('part_no');
            $table->string('part_name')->nullable();
            $table->string('uom', 50)->nullable();
            $table->decimal('qty_requested', 18, 4)->default(0);
            $table->decimal('qty_issued', 18, 4)->default(0);
            $table->decimal('stock_on_hand', 18, 4)->default(0);
            $table->decimal('stock_on_order', 18, 4)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('part_id');
            $table->foreign('production_material_request_id', 'pmr_items_request_fk')
                ->references('id')
                ->on('production_material_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_material_request_items');
    }
};
