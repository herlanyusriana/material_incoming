<?php

namespace App\Exports;

use App\Models\Machine;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MachinesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return Machine::query()
            ->select(['code', 'name', 'group_name', 'cycle_time', 'cycle_time_unit', 'is_active'])
            ->orderBy('cycle_time')
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'code',
            'name',
            'group_name',
            'cycle_time',
            'cycle_time_unit',
            'is_active',
        ];
    }

    public function map($machine): array
    {
        return [
            $machine->code,
            $machine->name,
            $machine->group_name,
            $machine->cycle_time,
            $machine->cycle_time_unit,
            $machine->is_active ? 'yes' : 'no',
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
            'A' => 15,
            'B' => 30,
            'C' => 20,
            'D' => 12,
            'E' => 15,
            'F' => 12,
        ];
    }
}
