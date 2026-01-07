<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('customer_part_no', 100);
            $table->string('customer_part_name', 255)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['customer_id', 'customer_part_no']);
            $table->index('customer_part_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_parts');
    }
};
