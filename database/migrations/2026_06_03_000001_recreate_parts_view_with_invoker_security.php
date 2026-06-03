<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        $boolCheck = $driver === 'pgsql'
            ? 'gpv.quality_inspection = true'
            : 'gpv.quality_inspection = 1';
        $security = $driver === 'mysql' ? 'SQL SECURITY INVOKER ' : '';

        DB::statement('DROP VIEW IF EXISTS parts');
        DB::statement("
            CREATE {$security}VIEW parts AS
            SELECT
                gpv.id,
                gpv.gci_part_id,
                gpv.vendor_id,
                gpv.vendor_part_no AS part_no,
                gpv.vendor_part_name AS part_name_vendor,
                gp.part_name AS part_name_gci,
                gpv.register_no,
                gpv.price,
                gpv.uom,
                gpv.hs_code,
                CASE WHEN {$boolCheck} THEN 'YES' ELSE NULL END AS quality_inspection,
                gpv.status,
                gpv.created_at,
                gpv.updated_at
            FROM gci_part_vendor gpv
            JOIN gci_parts gp ON gp.id = gpv.gci_part_id
        ");
    }

    public function down(): void
    {
        $this->up();
    }
};
