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
        Schema::table('customer_parts', function (Blueprint $table) {
            $table->renameColumn('part_no', 'customer_part_no');
            $table->renameColumn('part_name', 'customer_part_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_parts', function (Blueprint $table) {
            $table->renameColumn('customer_part_no', 'part_no');
            $table->renameColumn('customer_part_name', 'part_name');
        });
    }
};
