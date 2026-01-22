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
            $table->dropUnique(['part_no']);
        });

        Schema::table('parts', function (Blueprint $table) {
            $table->dropUnique('parts_part_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            $table->string('part_no', 100)->unique()->change();
        });

        Schema::table('parts', function (Blueprint $table) {
            $table->string('part_no')->unique()->change();
        });
    }
};
