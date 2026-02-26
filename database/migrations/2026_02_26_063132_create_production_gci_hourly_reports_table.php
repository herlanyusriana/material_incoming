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
        Schema::create('production_gci_hourly_reports', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('production_gci_work_order_id');
            $table->foreign('production_gci_work_order_id', 'gci_hourly_wo_id_foreign')
                ->references('id')->on('production_gci_work_orders')->onDelete('cascade');
            $table->string('time_range');
            $table->integer('target');
            $table->integer('actual');
            $table->integer('ng');
            $table->integer('offline_id')->comment('ID from SQLite device');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_gci_hourly_reports');
    }
};
