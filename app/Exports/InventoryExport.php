<?php

namespace App\Exports;

use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Incoming\IncomingReceive;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return InventoryLocationStock::query()
            ->with('gciPart')
            ->whereHas('gciPart')
            ->join('gci_parts as gp', 'gp.id', '=', 'inventory_location_stock.gci_part_id')
            ->addSelect(['inventory_location_stock.*'])
            ->addSelect(['latest_batch' => IncomingReceive::query()
                ->select('incoming_receives.tag')
                ->join('incoming_arrival_items', 'incoming_arrival_items.id', '=', 'incoming_receives.incoming_arrival_item_id')
                ->whereColumn('incoming_arrival_items.gci_part_id', 'inventory_location_stock.gci_part_id')
                ->whereNotNull('incoming_receives.tag')
                ->orderByDesc('incoming_receives.created_at')
                ->limit(1),
            ])
            ->orderByRaw("FIELD(gp.classification, 'RM', 'WIP', 'FG')")
            ->orderBy('gp.part_no')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Part No',
            'Part Name',
            'Model',
            'Classification',
            'Batch No',
            'On Hand',
            'On Order',
            'As Of Date',
        ];
    }

    public function map($stock): array
    {
        $part = $stock->gciPart;

        return [
            $part?->part_no ?? '',
            $part?->part_name ?? '',
            $part?->model ?? '',
            $part?->classification ?? '',
            $stock->latest_batch ?? '',
            (float) ($stock->qty_on_hand ?? 0),
            0, // on_order field not available in new schema
            $stock->last_counted_at ? \Carbon\Carbon::parse($stock->last_counted_at)->format('Y-m-d') : '',
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
            'A' => 20,
            'B' => 32,
            'C' => 20,
            'D' => 16,
            'E' => 24,
            'F' => 14,
            'G' => 14,
            'H' => 14,
        ];
    }
}
