<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('parts')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $columns = ['register_no', 'part_no', 'part_name_vendor', 'part_name_gci', 'hs_code'];
        $sets = collect($columns)
            ->filter(fn ($col) => Schema::hasColumn('parts', $col))
            ->map(fn ($col) => "{$col} = UPPER({$col})")
            ->implode(', ');

        if ($sets === '') {
            return;
        }

        DB::statement("UPDATE parts SET {$sets}");
    }

    public function down(): void
    {
        // Irreversible: lower/upper original casing can't be restored.
    }
};

