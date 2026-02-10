<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('outgoing_delivery_planning_lines', function (Blueprint $table) {
            $table->id();
            $table->date('delivery_date')->index();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

            // Truck trip quantities (1-14), manually input
            $table->integer('trip_1')->default(0);
            $table->integer('trip_2')->default(0);
            $table->integer('trip_3')->default(0);
            $table->integer('trip_4')->default(0);
            $table->integer('trip_5')->default(0);
            $table->integer('trip_6')->default(0);
            $table->integer('trip_7')->default(0);
            $table->integer('trip_8')->default(0);
            $table->integer('trip_9')->default(0);
            $table->integer('trip_10')->default(0);
            $table->integer('trip_11')->default(0);
            $table->integer('trip_12')->default(0);
            $table->integer('trip_13')->default(0);
            $table->integer('trip_14')->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['delivery_date', 'gci_part_id'], 'odpl_date_part_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_delivery_planning_lines');
    }
};
