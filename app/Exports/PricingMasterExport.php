<?php

namespace App\Exports;

use App\Models\PricingMaster;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PricingMasterExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection(): Collection
    {
        return PricingMaster::query()
            ->with(['gciPart', 'vendor', 'customer'])
            ->orderBy('gci_part_id')
            ->orderBy('price_type')
            ->orderByDesc('effective_from')
            ->get()
            ->map(function (PricingMaster $price) {
                return [
                    'part_no' => $price->gciPart?->part_no ?? '',
                    'price_type' => $price->price_type,
                    'vendor_name' => $price->vendor?->vendor_name ?? '',
                    'customer_name' => $price->customer?->name ?? '',
                    'currency' => $price->currency,
                    'uom' => $price->uom ?? '',
                    'min_qty' => $price->min_qty,
                    'price' => $price->price,
                    'effective_from' => $price->effective_from?->format('Y-m-d') ?? '',
                    'effective_to' => $price->effective_to?->format('Y-m-d') ?? '',
                    'status' => $price->status,
                    'notes' => $price->notes ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'part_no',
            'price_type',
            'vendor_name',
            'customer_name',
            'currency',
            'uom',
            'min_qty',
            'price',
            'effective_from',
            'effective_to',
            'status',
            'notes',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
