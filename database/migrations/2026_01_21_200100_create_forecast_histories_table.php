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
        if (!Schema::hasTable('forecast_histories')) {
            Schema::create('forecast_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('forecast_id')->constrained('forecasts')->onDelete('cascade');
                $table->decimal('qty_before', 20, 3);
                $table->decimal('qty_after', 20, 3);
                $table->string('changed_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('forecast_id');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_histories');
    }
};
