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

    public function __construct(Arrival $arrival)
    {
        $this->arrival = $arrival;
        $this->arrival->loadMissing(['vendor', 'items.receives', 'items.part']);

        $this->receivedTotalsByItemId = $this->arrival->items
            ->mapWithKeys(function ($item) {
                $total = (float) $item->receives->sum('qty');
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
            'planned_qty_goods',
            'planned_unit_goods',
            'received_qty',
            'received_qty_unit',
            'received_total_qty_for_item',
            'remaining_qty_for_item',
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
        $receivedTotal = (float) ($this->receivedTotalsByItemId->get((int) ($item?->id ?? 0), 0));
        $remaining = max(0, $plannedQty - $receivedTotal);

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
            strtoupper((string) ($item?->unit_goods ?? '')),
            (float) ($receive->qty ?? 0),
            strtoupper((string) ($receive->qty_unit ?? '')),
            $receivedTotal,
            $remaining,
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
            'N' => 16,
            'O' => 16,
            'P' => 12,
            'Q' => 14,
            'R' => 22,
            'S' => 20,
            'T' => 12,
            'U' => 12,
            'V' => 12,
            'W' => 12,
            'X' => 18,
        ];
    }
}

