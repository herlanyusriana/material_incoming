<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_gci_part', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('gci_part_id');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('gci_part_id')->references('id')->on('gci_parts')->onDelete('cascade');

            $table->unique(['customer_id', 'gci_part_id']);
        });

        // Migrate existing data
        DB::statement('
            INSERT INTO customer_gci_part (customer_id, gci_part_id, created_at, updated_at)
            SELECT customer_id, id, created_at, updated_at
            FROM gci_parts
            WHERE customer_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_gci_part');
    }
};
