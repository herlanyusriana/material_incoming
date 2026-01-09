<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CustomerPlanningMonthlyTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Planning' => new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithColumnWidths {
                public function headings(): array
                {
                    return [
                        'Part Number',
                        'Biz Type',
                        '2025-12',
                        '2026-01',
                        '2026-02',
                    ];
                }

                public function array(): array
                {
                    return [
                        ['GN-B312PQGB.ASWGKRB', 'Regular', 0, 148, 296],
                    ];
                }

                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
                {
                    return [
                        1 => ['font' => ['bold' => true]],
                    ];
                }

                public function columnWidths(): array
                {
                    return [
                        'A' => 26,
                        'B' => 14,
                        'C' => 14,
                        'D' => 14,
                        'E' => 14,
                    ];
                }
            },
            'WeekMap' => new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithColumnWidths {
                public function headings(): array
                {
                    return [
                        'month_key',
                        'minggu',
                        'ratio',
                    ];
                }

                public function array(): array
                {
                    return [
                        ['2026-01', '2026-W01', 0.25],
                        ['2026-01', '2026-W02', 0.25],
                        ['2026-01', '2026-W03', 0.25],
                        ['2026-01', '2026-W04', 0.25],
                        ['2026-02', '2026-W05', 0.25],
                        ['2026-02', '2026-W06', 0.25],
                        ['2026-02', '2026-W07', 0.25],
                        ['2026-02', '2026-W08', 0.25],
                    ];
                }

                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
                {
                    return [
                        1 => ['font' => ['bold' => true]],
                    ];
                }

                public function columnWidths(): array
                {
                    return [
                        'A' => 18,
                        'B' => 12,
                        'C' => 10,
                    ];
                }
            },
        ];
    }
}
