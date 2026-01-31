<?php

namespace App\Exports;

use App\Models\Trolly;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrolliesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return Trolly::query()->orderBy('code')->get();
    }

    public function headings(): array
    {
        return [
            'code',
            'type',
            'kind',
            'status',
        ];
    }

    public function map($trolly): array
    {
        return [
            $trolly->code,
            $trolly->type,
            $trolly->kind,
            $trolly->status,
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
            'B' => 15,
            'C' => 20,
            'D' => 12,
        ];
    }
}
