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
        Schema::create('location_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts')->onDelete('cascade');
            $table->string('location_code');
            $table->decimal('qty_on_hand', 20, 4)->default(0);
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamps();

            // Unique constraint: one record per part per location
            $table->unique(['part_id', 'location_code']);

            // Indexes
            $table->index('location_code');
            $table->index('qty_on_hand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_inventory');
    }
};
