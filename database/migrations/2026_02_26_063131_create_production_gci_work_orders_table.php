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
        Schema::create('production_gci_work_orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('order_no');
            $table->string('type_model');
            $table->decimal('tact_time', 8, 2);
            $table->integer('target_uph');
            $table->date('date');
            $table->string('shift');
            $table->string('foreman');
            $table->string('operator_name');
            $table->integer('offline_id')->comment('ID from SQLite device');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_gci_work_orders');
    }
};
