<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_requirement_fulfillments')) {
            return;
        }

        Schema::create('delivery_requirement_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->date('plan_date');
            $table->foreignId('row_id')->constrained('outgoing_daily_plan_rows')->cascadeOnDelete();
            $table->decimal('qty', 18, 4);
            $table->foreignId('delivery_plan_id')->nullable()->constrained('delivery_plans')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['plan_date', 'row_id']);
            $table->index('delivery_plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_requirement_fulfillments');
    }
};

