<?php

namespace App\Exports;

use App\Models\ProductionOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductionOrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'production_order_number',
            'transaction_no',
            'part_no',
            'part_name',
            'model',
            'process_name',
            'machine',
            'plan_date',
            'shift',
            'qty_planned',
            'qty_actual',
            'qty_ng',
            'status',
            'workflow_stage',
            'material_supply_qty',
            'material_consumed_qty',
            'material_returned_qty',
            'material_remaining_qty',
            'start_time',
            'end_time',
            'created_at',
        ];
    }

    public function map($order): array
    {
        /** @var ProductionOrder $order */
        $supplyQty = round((float) ($order->inventory_supply_total ?? 0), 4);
        $consumedQty = round((float) ($order->inventory_consumed_total ?? 0), 4);
        $returnedQty = round((float) ($order->inventory_returned_total ?? 0), 4);

        return [
            $order->production_order_number,
            $order->transaction_no,
            $order->part?->part_no,
            $order->part?->part_name,
            $order->part?->model,
            $order->process_name,
            $order->machine?->name ?? $order->machine_name,
            $this->formatDate($order->plan_date),
            $order->shift,
            (float) $order->qty_planned,
            (float) $order->qty_actual,
            (float) $order->qty_ng,
            $order->status,
            $order->workflow_stage,
            $supplyQty,
            $consumedQty,
            $returnedQty,
            max(0, round($supplyQty - $consumedQty - $returnedQty, 4)),
            $order->start_time ? (string) $order->start_time : '',
            $order->end_time ? (string) $order->end_time : '',
            $this->formatDateTime($order->created_at),
        ];
    }

    private function formatDate($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return $value instanceof \DateTimeInterface
                ? Carbon::instance($value)->format('Y-m-d')
                : Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function formatDateTime($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return $value instanceof \DateTimeInterface
                ? Carbon::instance($value)->format('Y-m-d H:i:s')
                : Carbon::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
