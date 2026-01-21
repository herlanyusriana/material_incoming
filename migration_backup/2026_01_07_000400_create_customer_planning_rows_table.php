<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_planning_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('customer_planning_imports')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('customer_part_no', 100);
            $table->string('minggu', 8);
            $table->decimal('qty', 15, 3)->default(0);
            $table->foreignId('part_id')->nullable()->constrained('gci_parts')->nullOnDelete()->cascadeOnUpdate();
            $table->string('row_status', 20)->default('accepted');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['minggu', 'row_status']);
            $table->index('customer_part_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_planning_rows');
    }
};
