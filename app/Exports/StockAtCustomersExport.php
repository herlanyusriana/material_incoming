<?php

namespace App\Exports;

use Carbon\CarbonImmutable;
use App\Models\StockAtCustomer;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockAtCustomersExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(private readonly string $period)
    {
    }

    public function array(): array
    {
        $rows = [];
        $daysInMonth = CarbonImmutable::parse($this->period . '-01')->daysInMonth;

        $records = StockAtCustomer::query()
            ->with(['customer', 'part'])
            ->where('period', $this->period)
            ->orderBy('customer_id')
            ->orderBy('part_no')
            ->get();

        foreach ($records as $rec) {
            $row = [
                $rec->customer?->name ?? '',
                $rec->part_no,
                $rec->part_name ?: ($rec->part?->part_name ?? ''),
                $rec->model ?: ($rec->part?->model ?? ''),
                $rec->status ?? '',
            ];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $row[] = (float) ($rec->{'day_' . $d} ?? 0);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function headings(): array
    {
        $base = ['customer', 'part_no', 'part_name', 'model', 'status'];
        $daysInMonth = CarbonImmutable::parse($this->period . '-01')->daysInMonth;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $base[] = (string) $d;
        }
        return $base;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
