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
            'component_part_no',
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

        return [
            $fgPartNo,
            $componentPartNo,
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
            'B' => 20,
            'C' => 20,
            'D' => 30,
            'E' => 10,
            'F' => 10,
            'G' => 10,
            'H' => 30,
        ];
    }
}
