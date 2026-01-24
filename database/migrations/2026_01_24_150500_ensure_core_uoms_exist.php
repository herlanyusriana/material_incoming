<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('uoms')) {
            return;
        }

        $rows = [
            ['code' => 'PCS', 'name' => 'Pieces', 'category' => 'quantity', 'is_active' => true],
            ['code' => 'ROLL', 'name' => 'Roll', 'category' => 'quantity', 'is_active' => true],
            ['code' => 'SHEET', 'name' => 'Sheet', 'category' => 'quantity', 'is_active' => true],
            ['code' => 'KGM', 'name' => 'Kilogram', 'category' => 'weight', 'is_active' => true],
        ];

        foreach ($rows as $row) {
            DB::table('uoms')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'category' => $row['category'],
                    'is_active' => (bool) $row['is_active'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // No-op: do not delete potentially-used UOMs.
    }
};

