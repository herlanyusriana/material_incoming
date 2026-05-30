<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('arrival_items')) {
            return;
        }

        if (Schema::hasColumn('arrival_items', 'weight_nett')) {
            DB::table('arrival_items')->whereNull('weight_nett')->update(['weight_nett' => 0]);
            DB::statement('ALTER TABLE arrival_items MODIFY weight_nett DECIMAL(20, 3) NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('arrival_items', 'weight_gross')) {
            DB::table('arrival_items')->whereNull('weight_gross')->update(['weight_gross' => 0]);
            DB::statement('ALTER TABLE arrival_items MODIFY weight_gross DECIMAL(20, 3) NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('arrival_items')) {
            return;
        }

        if (Schema::hasColumn('arrival_items', 'weight_nett')) {
            DB::statement('ALTER TABLE arrival_items MODIFY weight_nett DECIMAL(20, 3) NULL');
        }

        if (Schema::hasColumn('arrival_items', 'weight_gross')) {
            DB::statement('ALTER TABLE arrival_items MODIFY weight_gross DECIMAL(20, 3) NULL');
        }
    }
};
