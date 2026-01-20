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
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // Increase precision for arrival_items
        DB::statement('ALTER TABLE arrival_items MODIFY total_price DECIMAL(20,2) NOT NULL');
        DB::statement('ALTER TABLE arrival_items MODIFY price DECIMAL(20,3) NOT NULL');
        DB::statement('ALTER TABLE arrival_items MODIFY weight_nett DECIMAL(15,2) NOT NULL');
        DB::statement('ALTER TABLE arrival_items MODIFY weight_gross DECIMAL(15,2) NOT NULL');

        // Increase precision for receives
        DB::statement('ALTER TABLE receives MODIFY weight DECIMAL(15,2) NULL');
        DB::statement('ALTER TABLE receives MODIFY net_weight DECIMAL(15,2) NULL');
        DB::statement('ALTER TABLE receives MODIFY gross_weight DECIMAL(15,2) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // Revert precision for arrival_items
        DB::statement('ALTER TABLE arrival_items MODIFY total_price DECIMAL(10,2) NOT NULL');
        DB::statement('ALTER TABLE arrival_items MODIFY price DECIMAL(10,3) NOT NULL');
        DB::statement('ALTER TABLE arrival_items MODIFY weight_nett DECIMAL(10,2) NOT NULL');
        DB::statement('ALTER TABLE arrival_items MODIFY weight_gross DECIMAL(10,2) NOT NULL');

        // Revert precision for receives
        DB::statement('ALTER TABLE receives MODIFY weight DECIMAL(8,2) NULL');
        DB::statement('ALTER TABLE receives MODIFY net_weight DECIMAL(8,2) NULL');
        DB::statement('ALTER TABLE receives MODIFY gross_weight DECIMAL(8,2) NULL');
    }
};
