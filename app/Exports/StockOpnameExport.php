<?php

namespace App\Exports;

use App\Models\StockOpnameItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockOpnameExport implements FromCollection, WithHeadings, WithMapping
{
    protected $sessionId;

    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function collection()
    {
        return StockOpnameItem::with(['part', 'location', 'counter'])
            ->where('session_id', $this->sessionId)
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Location',
            'Part No',
            'Part Name',
            'Batch',
            'System Qty',
            'Counted Qty',
            'Difference',
            'Counter',
            'Time',
            'Notes',
            'Raw Barcode'
        ];
    }

    public function map($item): array
    {
        $diff = $item->counted_qty - $item->system_qty;

        return [
            $item->id,
            $item->location_code,
            $item->part?->part_no ?? ($item->barcode_raw ? 'UNKNOWN' : '-'),
            $item->part?->part_name ?? $item->barcode_raw,
            $item->batch,
            $item->system_qty,
            $item->counted_qty,
            $diff,
            $item->counter?->name ?? 'System',
            $item->counted_at?->toDateTimeString(),
            $item->notes,
            $item->barcode_raw
        ];
    }
}
