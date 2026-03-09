<?php

namespace App\Exports;

use App\Models\GciInventory;
use App\Models\LocationInventory;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GciInventoryExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function __construct(
        private readonly string $classification = '',
        private readonly string $status = '',
        private readonly string $search = '',
    ) {
    }

    public function array(): array
    {
        $query = GciInventory::query()
            ->with('part')
            ->when($this->classification !== '', fn($q) => $q->whereHas('part', fn($qp) => $qp->where('classification', $this->classification)))
            ->when(in_array($this->status, ['active', 'inactive'], true), fn($q) => $q->whereHas('part', fn($qp) => $qp->where('status', $this->status)))
            ->when($this->search !== '', function ($q) {
                $s = strtoupper($this->search);
                $q->whereHas('part', function ($qp) use ($s) {
                    $qp->where('part_no', 'like', '%' . $s . '%')
                        ->orWhere('part_name', 'like', '%' . $s . '%')
                        ->orWhere('model', 'like', '%' . $s . '%');
                });
            })
            ->orderByDesc('on_hand')
            ->orderBy('gci_part_id');

        $rows = [];
        foreach ($query->get() as $inv) {
            $part = $inv->part;
            if (!$part) {
                continue;
            }

            // Get locations with stock
            $locations = LocationInventory::where('gci_part_id', $inv->gci_part_id)
                ->where('qty_on_hand', '>', 0)
                ->orderByDesc('qty_on_hand')
                ->get()
                ->map(fn($l) => $l->location_code . ' (' . number_format((float) $l->qty_on_hand) . ')')
                ->implode(', ');

            $rows[] = [
                $part->part_no,
                $part->part_name,
                $part->model ?? '',
                strtoupper($part->classification ?? ''),
                $part->status ?? '',
                (float) ($inv->on_hand ?? 0),
                (float) ($inv->on_order ?? 0),
                $part->default_location ?? '',
                $locations ?: '-',
                $inv->as_of_date ? \Carbon\Carbon::parse($inv->as_of_date)->format('Y-m-d') : '',
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Part No',
            'Part Name',
            'Model',
            'Classification',
            'Status',
            'On Hand',
            'On Order',
            'Default Location',
            'Stock Locations',
            'As Of Date',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 30,
            'C' => 15,
            'D' => 14,
            'E' => 10,
            'F' => 12,
            'G' => 12,
            'H' => 16,
            'I' => 30,
            'J' => 12,
        ];
    }
}
