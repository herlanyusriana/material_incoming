<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_at_customers')) {
            return;
        }

        Schema::create('stock_at_customers', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7); // YYYY-MM
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('gci_part_id')->nullable()->constrained('gci_parts')->nullOnDelete();
            $table->string('part_no', 100);
            $table->string('part_name', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->string('status', 20)->nullable();

            for ($i = 1; $i <= 31; $i++) {
                $table->decimal('day_' . $i, 20, 3)->default(0);
            }

            $table->timestamps();

            $table->unique(['period', 'customer_id', 'part_no']);
            $table->index(['period', 'customer_id']);
            $table->index(['period', 'gci_part_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_at_customers');
    }
};

