<?php

namespace App\Exports;

use App\Models\Arrival;
use App\Models\Receive;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompletedInvoiceReceivesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    private Arrival $arrival;
    /** @var \Illuminate\Support\Collection<int, float> */
    private Collection $receivedTotalsByItemId;
    /** @var \Illuminate\Support\Collection<int, float> */
    private Collection $receivedWeightsByItemId;

    private function shouldConvertToKgm(?string $unit): bool
    {
        return in_array(strtoupper(trim((string) $unit)), ['SHEET', 'EA'], true);
    }

    private function exportKgmQty(mixed $qty, ?string $unit, mixed $weight): ?float
    {
        if ($this->shouldConvertToKgm($unit)) {
            return (float) ($weight ?? 0);
        }

        return null;
    }

    public function __construct(Arrival $arrival)
    {
        $this->arrival = $arrival;
        $this->arrival->loadMissing(['vendor', 'items.receives', 'items.part']);

        $this->receivedTotalsByItemId = $this->arrival->items
            ->mapWithKeys(function ($item) {
                $total = (float) $item->receives->sum('qty');
                return [$item->id => $total];
            });

        $this->receivedWeightsByItemId = $this->arrival->items
            ->mapWithKeys(function ($item) {
                $total = (float) $item->receives->sum(function ($receive) {
                    return (float) ($receive->net_weight ?? $receive->weight ?? 0);
                });

                return [$item->id => $total];
            });
    }

    public function collection()
    {
        return Receive::query()
            ->select('receives.*')
            ->join('arrival_items', 'receives.arrival_item_id', '=', 'arrival_items.id')
            ->join('parts', 'arrival_items.part_id', '=', 'parts.id')
            ->with(['arrivalItem.part', 'arrivalItem.arrival.vendor'])
            ->where('arrival_items.arrival_id', $this->arrival->id)
            ->orderBy('parts.part_no', 'asc')
            ->orderByRaw('LENGTH(receives.tag) ASC')
            ->orderBy('receives.tag', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'invoice_no',
            'invoice_date',
            'vendor',
            'arrival_no',
            'tag',
            'qc_status',
            'ata_date',
            'jo_po_number',
            'location_code',
            'part_no',
            'part_name_vendor',
            'material_group',
            'size',
            'planned_qty_goods_original',
            'planned_unit_goods_original',
            'planned_qty_kgm',
            'received_qty_original',
            'received_qty_unit_original',
            'received_qty_kgm',
            'received_total_qty_for_item',
            'received_total_qty_for_item_kgm',
            'remaining_qty_for_item',
            'remaining_qty_for_item_kgm',
            'bundle_qty',
            'bundle_unit',
            'net_weight',
            'gross_weight',
            'weight',
            'receive_created_at',
        ];
    }

    public function map($receive): array
    {
        $arrival = $receive->arrivalItem?->arrival;
        $item = $receive->arrivalItem;
        $part = $item?->part;

        $plannedQty = (float) ($item?->qty_goods ?? 0);
        $plannedWeight = (float) ($item?->weight_nett ?? 0);
        $goodsUnit = strtoupper((string) ($item?->unit_goods ?? ''));
        $receivedTotal = (float) ($this->receivedTotalsByItemId->get((int) ($item?->id ?? 0), 0));
        $receivedWeightTotal = (float) ($this->receivedWeightsByItemId->get((int) ($item?->id ?? 0), 0));
        $remainingOriginal = max(0, $plannedQty - $receivedTotal);
        $remainingKgm = max(0, $plannedWeight - $receivedWeightTotal);
        $receiveQtyUnit = strtoupper((string) ($receive->qty_unit ?? ''));

        return [
            $arrival?->invoice_no ?? '',
            optional($arrival?->invoice_date)->format('Y-m-d') ?? '',
            $arrival?->vendor?->vendor_name ?? '',
            $arrival?->arrival_no ?? '',
            $receive->tag ?? '',
            $receive->qc_status ?? '',
            optional($receive->ata_date)->format('Y-m-d H:i:s') ?? '',
            $receive->jo_po_number ?? '',
            $receive->location_code ?? '',
            $part?->part_no ?? '',
            $part?->part_name_vendor ?? '',
            $item?->material_group ?? '',
            $item?->size ?? '',
            $plannedQty ?: 0,
            $goodsUnit,
            $this->exportKgmQty($plannedQty, $goodsUnit, $plannedWeight),
            (float) ($receive->qty ?? 0),
            $receiveQtyUnit,
            $this->exportKgmQty($receive->qty, $receiveQtyUnit, $receive->net_weight ?? $receive->weight),
            $receivedTotal,
            $receivedWeightTotal > 0 ? $receivedWeightTotal : null,
            $remainingOriginal,
            $remainingKgm > 0 ? $remainingKgm : null,
            (int) ($receive->bundle_qty ?? 1),
            strtoupper((string) ($receive->bundle_unit ?? '')),
            $receive->net_weight !== null ? (float) $receive->net_weight : null,
            $receive->gross_weight !== null ? (float) $receive->gross_weight : null,
            $receive->weight !== null ? (float) $receive->weight : null,
            optional($receive->created_at)->format('Y-m-d H:i:s') ?? '',
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
            'B' => 12,
            'C' => 28,
            'D' => 14,
            'E' => 18,
            'F' => 10,
            'G' => 18,
            'H' => 14,
            'I' => 14,
            'J' => 14,
            'K' => 28,
            'L' => 18,
            'M' => 14,
            'N' => 18,
            'O' => 18,
            'P' => 14,
            'Q' => 18,
            'R' => 18,
            'S' => 18,
            'T' => 22,
            'U' => 18,
            'V' => 20,
            'W' => 18,
            'X' => 12,
            'Y' => 12,
            'Z' => 12,
            'AA' => 12,
            'AB' => 18,
        ];
    }
}
