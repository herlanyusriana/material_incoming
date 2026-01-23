<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('gci_parts', function (Blueprint $table) {
                $table->dropUnique('gci_parts_part_no_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if index does not exist (migration might have been applied partially before).
        }

        try {
            Schema::table('parts', function (Blueprint $table) {
                $table->dropUnique('parts_part_no_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if index does not exist (migration might have been applied partially before).
        }
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
