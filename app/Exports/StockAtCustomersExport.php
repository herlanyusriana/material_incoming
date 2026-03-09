<?php

namespace App\Exports;

use Carbon\CarbonImmutable;
use App\Models\StockAtCustomer;
use Illuminate\Support\Facades\DB;
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
        $date = CarbonImmutable::parse($this->period . '-01');
        $daysInMonth = $date->daysInMonth;
        $startDate = $date->format('Y-m-d');
        $endDate = $date->endOfMonth()->format('Y-m-d');

        // Fetch all rows for this period and group by customer+part
        $records = StockAtCustomer::query()
            ->with(['customer', 'part'])
            ->whereBetween('stock_date', [$startDate, $endDate])
            ->orderBy('customer_id')
            ->orderBy('part_no')
            ->orderBy('stock_date')
            ->get();

        // Pivot: group by customer_id + part_no, then spread qty across day columns
        $grouped = [];
        foreach ($records as $rec) {
            $key = $rec->customer_id . '|' . $rec->part_no;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'customer_name' => $rec->customer?->name ?? '',
                    'part_no' => $rec->part_no,
                    'part_name' => $rec->part_name ?: ($rec->part?->part_name ?? ''),
                    'model' => $rec->model ?: ($rec->part?->model ?? ''),
                    'status' => $rec->status ?? '',
                    'days' => array_fill(1, $daysInMonth, 0),
                ];
            }
            $dayNum = (int) $rec->stock_date->format('j');
            $grouped[$key]['days'][$dayNum] = (float) $rec->qty;
        }

        $rows = [];
        foreach ($grouped as $item) {
            $row = [
                $item['customer_name'],
                $item['part_no'],
                $item['part_name'],
                $item['model'],
                $item['status'],
            ];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $row[] = $item['days'][$d];
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function headings(): array
    {
        $base = ['customer', 'part_no', 'part_name', 'model', 'status'];
        $date = CarbonImmutable::parse($this->period . '-01');
        $daysInMonth = $date->daysInMonth;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            // Use plain day number to prevent Excel from auto-converting to Date format
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
