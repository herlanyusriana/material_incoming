<?php

namespace App\Exports;

use App\Models\Truck;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrucksExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(private readonly bool $template = false)
    {
    }

    public function array(): array
    {
        if ($this->template) {
            return [
                ['B 1234 XX', 'Box Truck', '5 Ton', 'available'],
            ];
        }

        return Truck::query()
            ->orderBy('plate_no')
            ->get()
            ->map(fn ($t) => [
                $t->plate_no,
                $t->type ?? '',
                $t->capacity ?? '',
                $t->status ?? 'available',
            ])
            ->all();
    }

    public function headings(): array
    {
        return ['plate_no', 'type', 'capacity', 'status'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

