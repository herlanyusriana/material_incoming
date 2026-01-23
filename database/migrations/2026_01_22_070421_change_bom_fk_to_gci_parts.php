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
        if (!Schema::hasTable('boms')) {
            return;
        }
        // Disable FK checks to avoid issues during switch if data exists
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        Schema::table('boms', function (Blueprint $table) {
            try {
                $table->dropForeign(['part_id']);
            } catch (\Throwable $e) {
            }
            try {
                $table->foreign('part_id')->references('id')->on('gci_parts')->cascadeOnDelete();
            } catch (\Throwable $e) {
            }
        });

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('boms')) {
            return;
        }
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        Schema::table('boms', function (Blueprint $table) {
            try {
                $table->dropForeign(['part_id']);
            } catch (\Throwable $e) {
            }
            try {
                $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
            } catch (\Throwable $e) {
            }
        });

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }
};
