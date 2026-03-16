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

            // Get all location records for this part
            $locRecords = LocationInventory::where('gci_part_id', $inv->gci_part_id)
                ->where('qty_on_hand', '>', 0)
                ->orderByDesc('qty_on_hand')
                ->get();

            if ($locRecords->isEmpty()) {
                // No location stock — export one row with default_location (can be filled in by user)
                $rows[] = [
                    $part->part_no,
                    $part->part_name,
                    $part->model ?? '',
                    strtoupper($part->classification ?? ''),
                    $part->default_location ?? '',
                    '',  // location_code — user fills this in
                    (float) ($inv->on_hand ?? 0),
                    '',  // batch_no
                ];
            } else {
                // One row per location — directly re-importable
                foreach ($locRecords as $loc) {
                    $rows[] = [
                        $part->part_no,
                        $part->part_name,
                        $part->model ?? '',
                        strtoupper($part->classification ?? ''),
                        $part->default_location ?? '',
                        $loc->location_code,
                        (float) $loc->qty_on_hand,
                        $loc->batch_no ?? '',
                    ];
                }
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'part_no',
            'part_name',
            'model',
            'classification',
            'default_location',
            'location_code',
            'qty',
            'batch_no',
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
            'A' => 20,
            'B' => 30,
            'C' => 15,
            'D' => 14,
            'E' => 16,
            'F' => 16,
            'G' => 12,
            'H' => 16,
        ];
    }
}