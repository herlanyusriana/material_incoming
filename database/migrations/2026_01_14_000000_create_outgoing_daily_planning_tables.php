<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outgoing_daily_plans')) {
            Schema::create('outgoing_daily_plans', function (Blueprint $table) {
                $table->id();
                $table->date('date_from');
                $table->date('date_to');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['date_from', 'date_to']);
            });
        }

        if (!Schema::hasTable('outgoing_daily_plan_rows')) {
            Schema::create('outgoing_daily_plan_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained('outgoing_daily_plans')->cascadeOnDelete();
                $table->unsignedInteger('row_no')->default(1);
                $table->string('production_line', 50);
                $table->string('part_no', 100);
                $table->timestamps();

                $table->index(['plan_id', 'row_no']);
            });
        }

        if (!Schema::hasTable('outgoing_daily_plan_cells')) {
            Schema::create('outgoing_daily_plan_cells', function (Blueprint $table) {
                $table->id();
                $table->foreignId('row_id')->constrained('outgoing_daily_plan_rows')->cascadeOnDelete();
                $table->date('plan_date');
                $table->unsignedInteger('seq')->nullable();
                $table->unsignedInteger('qty')->nullable();
                $table->timestamps();

                $table->unique(['row_id', 'plan_date']);
                $table->index(['plan_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_daily_plan_cells');
        Schema::dropIfExists('outgoing_daily_plan_rows');
        Schema::dropIfExists('outgoing_daily_plans');
    }
};

