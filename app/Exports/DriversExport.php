<?php

namespace App\Exports;

use App\Models\Driver;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DriversExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(private readonly bool $template = false)
    {
    }

    public function array(): array
    {
        if ($this->template) {
            return [
                ['Nama Driver', '08123456789', 'SIM B1', 'available'],
            ];
        }

        return Driver::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($d) => [
                $d->name,
                $d->phone ?? '',
                $d->license_type ?? '',
                $d->status ?? 'available',
            ])
            ->all();
    }

    public function headings(): array
    {
        return ['name', 'phone', 'license_type', 'status'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

