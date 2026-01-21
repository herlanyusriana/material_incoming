<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('arrival_items') || !Schema::hasColumn('arrival_items', 'price')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE arrival_items MODIFY price DECIMAL(10,3) NOT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('arrival_items') || !Schema::hasColumn('arrival_items', 'price')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE arrival_items MODIFY price DECIMAL(10,2) NOT NULL');
    }
};

