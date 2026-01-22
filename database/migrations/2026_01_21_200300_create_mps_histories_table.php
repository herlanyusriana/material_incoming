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
        if (!Schema::hasTable('mps_histories')) {
            Schema::create('mps_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mps_id')->constrained('mps')->onDelete('cascade');
                $table->decimal('planned_qty_before', 20, 3);
                $table->decimal('planned_qty_after', 20, 3);
                $table->string('changed_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('mps_id');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mps_histories');
    }
};
