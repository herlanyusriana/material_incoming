<?php

namespace App\Exports;

use App\Models\GciPart;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GciPartsExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function collection()
    {
        $parts = GciPart::with([
            'customer',
            'bom.items.componentPart',
            'bom.items.substitutes.part',
        ])
            ->orderBy('classification')
            ->orderBy('part_no')
            ->get();

        $rows = collect();

        foreach ($parts as $part) {
            $bomItems = $part->bom?->items ?? collect();
            $hasSubstitutes = false;

            foreach ($bomItems as $item) {
                foreach ($item->substitutes as $sub) {
                    $hasSubstitutes = true;
                    $rows->push([
                        $part->customer?->name ?? '',
                        $part->part_no,
                        $part->classification ?? 'FG',
                        $part->part_name ?? '',
                        $part->model ?? '',
                        $part->status ?? 'active',
                        $item->componentPart?->part_no ?? $item->component_part_no ?? '',
                        $item->componentPart?->part_name ?? '',
                        $sub->part?->part_no ?? $sub->substitute_part_no ?? '',
                        $sub->part?->part_name ?? '',
                        $sub->ratio ?? 1,
                        $sub->priority ?? 1,
                        $sub->status ?? 'active',
                        $sub->notes ?? '',
                    ]);
                }
            }

            if (!$hasSubstitutes) {
                $rows->push([
                    $part->customer?->name ?? '',
                    $part->part_no,
                    $part->classification ?? 'FG',
                    $part->part_name ?? '',
                    $part->model ?? '',
                    $part->status ?? 'active',
                    '', '', '', '', '', '', '', '',
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'customer',
            'part_no',
            'classification',
            'part_name',
            'model',
            'status',
            'component_part_no',
            'component_part_name',
            'substitute_part_no',
            'substitute_part_name',
            'substitute_ratio',
            'substitute_priority',
            'substitute_status',
            'substitute_notes',
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
            'G' => 22,
            'H' => 30,
            'I' => 22,
            'J' => 30,
            'K' => 12,
            'L' => 12,
            'M' => 14,
            'N' => 30,
        ];
    }
}
