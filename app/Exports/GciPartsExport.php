<?php

namespace App\Exports;

use App\Models\GciPart;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GciPartsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return GciPart::query()
            ->with('customer')
            ->where('classification', 'FG')
            ->orderBy('part_no');
    }

    public function headings(): array
    {
        return ['customer', 'part_no', 'classification', 'part_name', 'model', 'status'];
    }

    public function map($part): array
    {
        return [
            $part->customer->code ?? '-',
            $part->part_no,
            $part->classification ?? 'FG',
            $part->part_name,
            $part->model,
            $part->status ?? 'active',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 22,
            'C' => 14,
            'D' => 40,
            'E' => 18,
            'F' => 12,
        ];
    }
}
