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
        if (!Schema::hasTable('customer_planning_rows')) {
            Schema::create('customer_planning_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('import_id')->constrained('customer_planning_imports')->onDelete('cascade');
                $table->foreignId('part_id')->nullable()->constrained('gci_parts')->onDelete('cascade');
                $table->string('customer_part_no')->nullable();
                $table->string('period', 7); // YYYY-MM or YYYY-WW (renamed from minggu)
                $table->decimal('qty', 15, 3)->default(0);
                $table->string('row_status', 20)->default('pending'); // 'accepted', 'rejected', 'pending'
                $table->text('error_message')->nullable();

                // From new migration
                $table->json('weekly_quantities')->nullable(); // {"2026-W01": 100, ...}

                $table->timestamps();

                // Indexes
                $table->index('import_id');
                $table->index('part_id');
                $table->index('period');
                $table->index('row_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_planning_rows');
    }
};
