<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('period', 7); // YYYY-MM
            $table->decimal('forecast_qty', 15, 3)->default(0);
            $table->decimal('open_order_qty', 15, 3)->default(0);
            $table->decimal('planned_qty', 15, 3)->default(0);
            $table->string('status', 20)->default('draft'); // draft | approved
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'period']);
            $table->index(['period', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mps');
    }
};

