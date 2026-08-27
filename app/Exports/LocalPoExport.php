<?php

namespace App\Exports;

use App\Models\NewSchema\Incoming\IncomingArrival;
use App\Models\NewSchema\Incoming\IncomingArrivalItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LocalPoExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return IncomingArrivalItem::query()
            ->with(['incomingArrival.vendor', 'gciPart', 'incomingReceives'])
            ->whereHas('incomingArrival.vendor', fn($q) => $q->where('vendor_type', 'local'))
            ->join('incoming_arrivals', 'incoming_arrivals.id', '=', 'incoming_arrival_items.incoming_arrival_id')
            ->orderByDesc('incoming_arrivals.invoice_date')
            ->orderBy('incoming_arrivals.invoice_no')
            ->select('incoming_arrival_items.*')
            ->get();
    }

    public function headings(): array
    {
        return [
            'PO No',
            'PO Date',
            'Vendor',
            'Part No',
            'Part Name',
            'Size',
            'Qty Ordered',
            'Unit',
            'Price',
            'Total Price',
            'Qty Received',
            'Remaining',
            'Currency',
        ];
    }

    public function map($item): array
    {
        $arrival = $item->incomingArrival;
        $part = $item->gciPart;
        $receivedQty = $item->incomingReceives->sum('qty');
        $remaining = max(0, (float) $item->qty_goods - (float) $receivedQty);

        return [
            $arrival?->invoice_no ?? '',
            $arrival?->invoice_date?->format('Y-m-d') ?? '',
            $arrival?->vendor?->vendor_name ?? '',
            $part?->part_no ?? '',
            $part?->part_name ?? '',
            $item->size ?? '',
            $item->qty_goods,
            $item->unit_goods ?? '',
            $item->price,
            $item->total_price,
            $receivedQty,
            $remaining,
            $arrival?->currency ?? 'IDR',
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
            'A' => 22,
            'B' => 14,
            'C' => 24,
            'D' => 20,
            'E' => 28,
            'F' => 14,
            'G' => 14,
            'H' => 10,
            'I' => 14,
            'J' => 16,
            'K' => 14,
            'L' => 14,
            'M' => 10,
        ];
    }
}
