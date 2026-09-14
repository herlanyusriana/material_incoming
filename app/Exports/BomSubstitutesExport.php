<?php

namespace App\Exports;

use App\Models\MaterialSubstitute;
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
        return MaterialSubstitute::query()
            ->with(['genericPart:id,part_no,part_name', 'substitutePart:id,part_no,part_name'])
            ->get();
    }

    public function headings(): array
    {
        return [
            'generic_part_no',
            'generic_part_name',
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
        return [
            $sub->genericPart->part_no ?? '',
            $sub->genericPart->part_name ?? '',
            $sub->substitutePart->part_no ?? '',
            $sub->substitutePart->part_name ?? '',
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
            'E' => 10,
            'F' => 10,
            'G' => 10,
            'H' => 30,
        ];
    }
}