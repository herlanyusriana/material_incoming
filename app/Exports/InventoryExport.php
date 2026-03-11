<?php

namespace App\Exports;

use App\Models\GciInventory;
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
        return GciInventory::query()
            ->with('part')
            ->whereHas('part')
            ->join('gci_parts as gp', 'gp.id', '=', 'gci_inventories.gci_part_id')
            ->addSelect(['gci_inventories.*'])
            ->addSelect(['latest_batch' => \App\Models\Receive::query()
                ->select('receives.tag')
                ->join('arrival_items', 'arrival_items.id', '=', 'receives.arrival_item_id')
                ->whereColumn('arrival_items.gci_part_id', 'gci_inventories.gci_part_id')
                ->whereNotNull('receives.tag')
                ->orderByDesc('receives.created_at')
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

    public function map($inventory): array
    {
        $part = $inventory->part;

        return [
            $part?->part_no ?? '',
            $part?->part_name ?? '',
            $part?->model ?? '',
            $part?->classification ?? '',
            $inventory->latest_batch ?? '',
            $inventory->on_hand,
            $inventory->on_order,
            $inventory->as_of_date ? \Carbon\Carbon::parse($inventory->as_of_date)->format('Y-m-d') : '',
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
