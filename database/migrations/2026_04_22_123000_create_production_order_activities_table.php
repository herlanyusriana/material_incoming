<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('production_order_activities')) {
            return;
        }

        Schema::create('production_order_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->string('activity_type', 50);
            $table->string('process_name')->nullable();
            $table->foreignId('machine_id')->nullable()->constrained('machines')->nullOnDelete();
            $table->string('machine_name')->nullable();
            $table->string('shift', 50)->nullable();
            $table->string('operator_name')->nullable();
            $table->string('output_type', 20)->nullable();
            $table->string('output_part_no')->nullable();
            $table->string('output_part_name')->nullable();
            $table->decimal('qty_ok', 18, 4)->default(0);
            $table->decimal('qty_ng', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['production_order_id', 'created_at'], 'po_activities_order_created_idx');
            $table->index(['machine_id', 'created_at'], 'po_activities_machine_created_idx');
            $table->index(['activity_type', 'created_at'], 'po_activities_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_activities');
    }
};
