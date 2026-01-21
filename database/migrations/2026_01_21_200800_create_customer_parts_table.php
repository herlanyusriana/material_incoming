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
        Schema::create('customer_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('part_no');
            $table->string('part_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('customer_id');
            $table->index('part_no');
            $table->unique(['customer_id', 'part_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_parts');
    }
};
