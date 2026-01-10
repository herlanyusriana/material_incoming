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
            'vendor',
            'vendor_type',
            'part_no',
            'size',
            'part_name_vendor',
            'part_name_gci',
            'hs_code',
            'quality_inspection',
            'status',
        ];
    }

    public function map($part): array
    {
        return [
            $part->vendor->vendor_name ?? '',
            $part->vendor->vendor_type ?? 'import',
            $part->part_no,
            $part->register_no,
            $part->part_name_vendor,
            $part->part_name_gci,
            $part->hs_code,
            strtoupper((string) ($part->quality_inspection ?? '')) === 'YES' ? 'YES' : '-',
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
            'A' => 25,
            'B' => 14,
            'C' => 18,
            'D' => 18,
            'E' => 30,
            'F' => 30,
            'G' => 14,
            'H' => 18,
            'I' => 12,
        ];
    }
}
