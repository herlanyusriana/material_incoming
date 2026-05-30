<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->parts() as $part) {
            DB::table('gci_parts')->updateOrInsert(
                ['part_no' => $part['part_no']],
                [
                    'barcode' => $part['part_no'],
                    'part_name' => $part['part_name'],
                    'classification' => 'WIP',
                    'status' => 'active',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('gci_parts')
            ->whereIn('part_no', array_column($this->parts(), 'part_no'))
            ->delete();
    }

    private function parts(): array
    {
        return [
            [
                'part_no' => 'MAZ65643601-PG',
                'part_name' => 'BRACKET,HANDLE - ALPHA 8 F',
            ],
            [
                'part_no' => 'MIFA62123401-PG',
                'part_name' => 'LEG FRAME - VT',
            ],
        ];
    }
};
