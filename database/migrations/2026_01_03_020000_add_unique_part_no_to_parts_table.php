<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('parts') || !Schema::hasColumn('parts', 'part_no')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table('parts', function (Blueprint $table) {
                $table->unique('part_no', 'parts_part_no_unique');
            });
            return;
        }

        DB::statement('UPDATE parts SET part_no = UPPER(TRIM(part_no)) WHERE part_no IS NOT NULL');

        $duplicates = DB::table('parts')
            ->select('part_no', DB::raw('COUNT(*) as cnt'))
            ->groupBy('part_no')
            ->having('cnt', '>', 1)
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $summary = $duplicates
                ->map(fn ($row) => "{$row->part_no} ({$row->cnt}x)")
                ->implode(', ');
            throw new RuntimeException("Cannot add unique index on parts.part_no; duplicates found: {$summary}");
        }

        Schema::table('parts', function (Blueprint $table) {
            $table->unique('part_no', 'parts_part_no_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('parts')) {
            return;
        }

        Schema::table('parts', function (Blueprint $table) {
            $table->dropUnique('parts_part_no_unique');
        });
    }
};

