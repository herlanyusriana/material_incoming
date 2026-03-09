<?php

namespace App\Exports;

use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Empty template for Stock at Customers import (7-day window).
 * Downloads a spreadsheet with headers only: customer, part_no, part_name, model, status, date1..date7.
 */
class StockAtCustomersTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(private readonly string $startDate)
    {
    }

    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        $base = ['customer', 'part_no', 'part_name', 'model', 'status'];
        $start = CarbonImmutable::parse($this->startDate);
        for ($i = 0; $i < 7; $i++) {
            $base[] = $start->addDays($i)->format('Y-m-d');
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
