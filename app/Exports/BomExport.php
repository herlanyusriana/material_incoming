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
            ->with(['part', 'items.wipPart', 'items.componentPart', 'items.machine'])
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
		                    '',
		                    $bom->part?->part_name ?? '',
		                    $bom->part?->model ?? '',
		                    $bom->part?->part_no ?? '',
		                    '',
		                    '',
		                    '',
		                    '',
		                    '',
		                    '',
		                    '',
		                    '',
		                    '',
		                    '',
		                    '',
                            '',
		                    '',
		                    '',
		                ];
		                continue;
		            }

            $seq = 0;
            foreach ($bom->items->sortBy(fn ($i) => $i->line_no ?? 0) as $item) {
                $seq++;
                $lineNo = $item->line_no !== null ? (string) $item->line_no : (string) $seq;
                $rows[] = [
                    $lineNo,
                    $bom->part?->part_name ?? '',
                    $bom->part?->model ?? '',
                    $bom->part?->part_no ?? '',
                    $item->process_name ?? '',
                    $item->machine?->name ?? '',
                    $item->wipPart?->part_no ?? '',
                    $item->wip_qty !== null ? (string) $item->wip_qty : '',
                    $item->wip_uom ?? '',
                    $item->wip_part_name ?: ($item->wipPart?->part_name ?? ''),
                    $item->material_size ?? '',
                    $item->material_spec ?? '',
                    $item->material_name ?? '',
                    $item->special ?? '',
                    $item->componentPart?->part_no ?? '',
                    strtoupper((string) ($item->make_or_buy ?? 'buy')),
                    (string) $item->usage_qty,
                    $item->consumption_uom ?? '',
                ];
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No',
            'FG Name',
            'FG Model',
            'FG Part No.',
            'Process Name',
            'Machine Name',
            'WIP Part No.',
            'QTY_WIP',
            'UOM_WIP',
            'WIP Part Name',
            'Material Size',
            'Material Spec',
            'Material Name',
            'spesial',
            'RM Part No.',
            'Make/Buy',
            'Consumption',
            'UOM_RM',
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
            'A' => 6,
            'B' => 26,
            'C' => 18,
            'D' => 18,
            'E' => 18,
            'F' => 18,
            'G' => 18,
            'H' => 10,
            'I' => 10,
            'J' => 26,
            'K' => 18,
            'L' => 18,
            'M' => 18,
            'N' => 14,
            'O' => 18,
            'P' => 10,
            'Q' => 14,
            'R' => 10,
        ];
    }
}
