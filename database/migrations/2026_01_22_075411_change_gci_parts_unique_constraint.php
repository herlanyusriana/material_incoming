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
            // Add new composite unique index
            // using a shorter name to avoid length limits potentially? 'gci_parts_pn_cid_unique'
            try {
                $table->unique(['part_no', 'customer_id'], 'gci_parts_part_no_customer_id_unique');
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
                $table->dropUnique('gci_parts_part_no_customer_id_unique');
            } catch (\Throwable $e) {
            }
            try {
                $table->unique('part_no');
            } catch (\Throwable $e) {
            }
        });
    }
};
