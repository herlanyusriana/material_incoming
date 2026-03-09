<?php

namespace App\Exports;

use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Empty template for Stock at Customers import.
 * Downloads a spreadsheet with headers only (customer, part_no, part_name, model, status, 1..N days).
 */
class StockAtCustomersTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(private readonly string $period)
    {
    }

    public function array(): array
    {
        // Return an empty array — template has headers only
        return [];
    }

    public function headings(): array
    {
        $base = ['customer', 'part_no', 'part_name', 'model', 'status'];
        $date = CarbonImmutable::parse($this->period . '-01');
        $daysInMonth = $date->daysInMonth;
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
