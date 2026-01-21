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
        Schema::create('mps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('gci_parts')->onDelete('cascade');
            $table->string('period', 7); // Monthly period: YYYY-MM
            $table->decimal('forecast_qty', 20, 3)->default(0);
            $table->decimal('open_order_qty', 20, 3)->default(0);
            $table->decimal('planned_qty', 20, 3)->default(0);
            $table->string('status')->default('draft'); // 'draft', 'approved', 'locked'
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('part_id');
            $table->index('period');
            $table->index('status');
            $table->unique(['part_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mps');
    }
};
