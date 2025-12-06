<?php

namespace App\Exports;

use App\Models\Part;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PartsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return Part::with('vendor')->orderBy('part_no')->get();
    }

    public function headings(): array
    {
        return [
            'part_number',
            'part_name_vendor',
            'part_name_internal',
            'vendor',
            'description',
            'status',
        ];
    }

    public function map($part): array
    {
        return [
            $part->part_no,
            $part->part_name_vendor,
            $part->part_name_gci,
            $part->vendor->vendor_name ?? '',
            $part->description,
            $part->status ?? 'active',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 30,
            'C' => 30,
            'D' => 25,
            'E' => 40,
            'F' => 12,
        ];
    }
}
