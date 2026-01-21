<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrp_purchase_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mrp_run_id')->constrained('mrp_runs')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('required_qty', 15, 3)->default(0);
            $table->decimal('on_hand', 15, 3)->default(0);
            $table->decimal('on_order', 15, 3)->default(0);
            $table->decimal('net_required', 15, 3)->default(0);
            $table->timestamps();

            $table->index('part_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrp_purchase_plans');
    }
};
