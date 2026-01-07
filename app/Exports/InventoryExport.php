<?php

namespace App\Exports;

use App\Models\Inventory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return Inventory::with('part')->orderBy('part_id')->get();
    }

    public function headings(): array
    {
        return [
            'part_no',
            'part_name_gci',
            'on_hand',
            'on_order',
            'as_of_date',
        ];
    }

    public function map($inventory): array
    {
        return [
            $inventory->part->part_no ?? '',
            $inventory->part->part_name_gci ?? '',
            $inventory->on_hand,
            $inventory->on_order,
            optional($inventory->as_of_date)->format('Y-m-d'),
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
            'A' => 18,
            'B' => 32,
            'C' => 14,
            'D' => 14,
            'E' => 14,
        ];
    }
}
