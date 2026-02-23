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
        Schema::create('production_downtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->time('start_time'); // HH:MM
            $table->time('end_time')->nullable(); // HH:MM (null if ongoing)
            $table->integer('duration_minutes')->nullable();
            $table->string('category'); // e.g., 'qdc', 'refill', 'setting', 'breakdown', 'other'
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_downtimes');
    }
};
