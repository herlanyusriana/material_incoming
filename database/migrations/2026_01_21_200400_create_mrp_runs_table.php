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
        Schema::create('mrp_runs', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7); // Monthly period: YYYY-MM
            $table->string('status')->default('running'); // 'running', 'completed', 'failed'
            $table->foreignId('run_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('run_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('period');
            $table->index('status');
            $table->index('run_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mrp_runs');
    }
};
