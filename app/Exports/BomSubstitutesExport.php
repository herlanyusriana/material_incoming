<?php

namespace App\Exports;

use App\Models\BomItemSubstitute;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BomSubstitutesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return BomItemSubstitute::query()
            ->with(['bomItem.bom.part', 'bomItem.componentPart', 'part'])
            ->get();
    }

    public function headings(): array
    {
        return [
            'fg_part_no',
            'fg_part_name',
            'component_part_no',
            'component_part_name',
            'substitute_part_no',
            'substitute_part_name',
            'ratio',
            'priority',
            'status',
            'notes',
        ];
    }

    public function map($sub): array
    {
        $item = $sub->bomItem;
        $componentPartNo = $item->component_part_no ?: ($item->componentPart?->part_no ?? '');
        $fgPartNo = $item->bom?->part?->part_no ?? '';
        $fgPartName = $item->bom?->part?->part_name ?? '';
        $componentPartName = $item->componentPart?->part_name ?? '';

        return [
            $fgPartNo,
            $fgPartName,
            $componentPartNo,
            $componentPartName,
            $sub->part?->part_no ?? '',
            $sub->part?->part_name ?? '',
            $sub->ratio,
            $sub->priority,
            $sub->status,
            $sub->notes,
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
            'C' => 20,
            'D' => 30,
            'E' => 20,
            'F' => 30,
            'G' => 10,
            'H' => 10,
            'I' => 10,
            'J' => 30,
        ];
    }
}
