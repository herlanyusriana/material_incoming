<?php

namespace App\Exports;

use App\Models\WarehouseLocation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WarehouseLocationsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return WarehouseLocation::query()->orderBy('location_code')->get();
    }

    public function headings(): array
    {
        return [
            'location_code',
            'class',
            'zone',
            'qr_payload',
            'status',
        ];
    }

    public function map($location): array
    {
        return [
            $location->location_code,
            $location->class,
            $location->zone,
            $location->qr_payload,
            $location->status,
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
            'B' => 10,
            'C' => 12,
            'D' => 64,
            'E' => 12,
        ];
    }
}

