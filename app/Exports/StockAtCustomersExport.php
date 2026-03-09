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
    public function __construct(private readonly string $startDate)
    {
    }

    public function array(): array
    {
        $start = CarbonImmutable::parse($this->startDate);
        $end = $start->addDays(6);
        $startStr = $start->format('Y-m-d');
        $endStr = $end->format('Y-m-d');

        // Build date keys for 7 days
        $dateKeys = [];
        for ($i = 0; $i < 7; $i++) {
            $dateKeys[] = $start->addDays($i)->format('Y-m-d');
        }

        // Fetch all rows for this 7-day range and group by customer+part
        $records = StockAtCustomer::query()
            ->with(['customer', 'part'])
            ->whereBetween('stock_date', [$startStr, $endStr])
            ->orderBy('customer_id')
            ->orderBy('part_no')
            ->orderBy('stock_date')
            ->get();

        // Pivot: group by customer_id + part_no, then spread qty across date columns
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
                    'days' => array_fill_keys($dateKeys, 0),
                ];
            }
            $dateKey = $rec->stock_date->format('Y-m-d');
            if (isset($grouped[$key]['days'][$dateKey])) {
                $grouped[$key]['days'][$dateKey] = (float) $rec->qty;
            }
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
            foreach ($dateKeys as $dk) {
                $row[] = $item['days'][$dk];
            }
            $rows[] = $row;
        }

        return $rows;
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
