<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Parts with BOM → FG
        DB::statement("
            UPDATE gci_parts gp
            SET gp.classification = 'FG'
            WHERE (gp.classification IS NULL OR TRIM(gp.classification) = '')
              AND EXISTS (SELECT 1 FROM boms WHERE boms.part_id = gp.id)
        ");

        // Remaining NULL → FG (safe default)
        DB::statement("
            UPDATE gci_parts
            SET classification = 'FG'
            WHERE classification IS NULL OR TRIM(classification) = ''
        ");
    }

    public function down(): void
    {
        // Cannot reliably revert — we don't know which were originally NULL
    }
};
