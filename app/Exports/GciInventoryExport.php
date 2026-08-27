<?php

namespace App\Exports;

use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
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
        $query = GciPart::query()
            ->when($this->classification !== '', fn($q) => $q->where('classification', $this->classification))
            ->when(in_array($this->status, ['active', 'inactive'], true), fn($q) => $q->where('status', $this->status))
            ->when($this->search !== '', function ($q) {
                $s = strtoupper($this->search);
                $q->where(function ($qp) use ($s) {
                    $qp->where('part_no', 'like', '%' . $s . '%')
                        ->orWhere('part_name', 'like', '%' . $s . '%')
                        ->orWhere('model', 'like', '%' . $s . '%');
                });
            })
            ->orderBy('part_no');

        $rows = [];
        foreach ($query->get() as $part) {
            $locRecords = InventoryLocationStock::where('gci_part_id', $part->id)
                ->where('qty_on_hand', '>', 0)
                ->orderByDesc('qty_on_hand')
                ->get();

            if ($locRecords->isEmpty()) {
                $rows[] = [
                    $part->part_no,
                    $part->part_name,
                    $part->model ?? '',
                    strtoupper($part->classification ?? ''),
                    $part->default_location ?? '',
                    '',  // location_code
                    0.0,
                    '',  // batch_no
                ];
            } else {
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
