<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            // Drop unique constraint on barcode
            try {
                $table->dropUnique('gci_parts_barcode_unique');
            } catch (\Throwable $e) {
            }
            
            // Add composite unique index for barcode similar to part_no
            try {
                $table->unique(['barcode', 'customer_id'], 'gci_parts_barcode_customer_unique');
            } catch (\Throwable $e) {
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            try {
                $table->dropUnique('gci_parts_barcode_customer_unique');
            } catch (\Throwable $e) {
            }
            try {
                $table->unique('barcode', 'gci_parts_barcode_unique');
            } catch (\Throwable $e) {
            }
        });
    }
};
