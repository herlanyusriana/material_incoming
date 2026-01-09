<?php

namespace App\Exports;

use App\Models\Bom;
use App\Models\GciPart;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BomExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function __construct(
        private readonly mixed $gciPartId = null,
        private readonly string $q = '',
    ) {
    }

    public function array(): array
    {
        $boms = Bom::query()
            ->with(['part', 'items.componentPart'])
            ->when($this->gciPartId, fn ($query) => $query->where('part_id', $this->gciPartId))
            ->when($this->q !== '', function ($query) {
                $query->whereHas('part', function ($sub) {
                    $sub->where('part_no', 'like', '%' . $this->q . '%')
                        ->orWhere('part_name', 'like', '%' . $this->q . '%');
                });
            })
            ->orderBy(GciPart::select('part_no')->whereColumn('gci_parts.id', 'boms.part_id'))
            ->get();

        $rows = [];
        foreach ($boms as $bom) {
            if ($bom->items->isEmpty()) {
                $rows[] = [
                    $bom->part?->part_no ?? '',
                    $bom->part?->part_name ?? '',
                    $bom->status ?? '',
                    '',
                    '',
                    '',
                ];
                continue;
            }

            foreach ($bom->items as $item) {
                $rows[] = [
                    $bom->part?->part_no ?? '',
                    $bom->part?->part_name ?? '',
                    $bom->status ?? '',
                    $item->componentPart?->part_no ?? '',
                    $item->componentPart?->part_name_gci ?? '',
                    (string) $item->usage_qty,
                ];
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'parent_part_no',
            'parent_part_name',
            'status',
            'component_part_no',
            'component_part_name',
            'usage_qty',
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
            'A' => 18,
            'B' => 30,
            'C' => 10,
            'D' => 18,
            'E' => 30,
            'F' => 12,
        ];
    }
}
