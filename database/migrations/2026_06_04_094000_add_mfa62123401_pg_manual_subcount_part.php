<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('gci_parts')->updateOrInsert(
            ['part_no' => 'MFA62123401-PG'],
            [
                'barcode' => 'MFA62123401-PG',
                'part_name' => 'LEG FRAME - VT',
                'classification' => 'WIP',
                'status' => 'active',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('gci_parts')
            ->where('part_no', 'MFA62123401-PG')
            ->delete();
    }
};
