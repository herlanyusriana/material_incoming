<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('delivery_plan_requirement_assignments')) {
            Schema::create('delivery_plan_requirement_assignments', function (Blueprint $table) {
                $table->id();
                $table->date('plan_date')->index();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();
                $table->string('status')->default('pending'); // pending, assigned, picking, shipped
                $table->foreignId('delivery_plan_id')->nullable()->constrained('delivery_plans')->nullOnDelete();
                $table->timestamps();

                $table->unique(['plan_date', 'gci_part_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_plan_requirement_assignments');
    }
};

