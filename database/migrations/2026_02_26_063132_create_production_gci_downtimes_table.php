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
        Schema::create('production_gci_downtimes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('production_gci_work_order_id')->constrained()->onDelete('cascade');
            $table->string('start_time');
            $table->string('end_time')->nullable();
            $table->integer('duration_minutes');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->integer('offline_id')->comment('ID from SQLite device');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_gci_downtimes');
    }
};
