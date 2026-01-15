<?php

namespace App\Exports;

use App\Models\Arrival;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DepartureDetailExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    private Arrival $arrival;

    public function __construct(Arrival $arrival)
    {
        $this->arrival = $arrival;
        $this->arrival->loadMissing(['vendor', 'trucking', 'items.part']);
    }

    public function collection()
    {
        return $this->arrival->items;
    }

    public function headings(): array
    {
        return [
            'Arrival No',
            'Invoice No',
            'Invoice Date',
            'Vendor',
            'Vessel',
            'Trucking',
            'ETD',
            'ETA JKT',
            'ETA GCI',
            'Port of Loading',
            'Bill of Lading',
            'BL Status',
            'Primary HS Code',
            'All HS Codes',
            'Containers',
            'Seal Code',
            'Notes (Shipment)',
            'Part No',
            'Part Name (GCI)',
            'Part Name (Vendor)',
            'Size',
            'Material Group',
            'Qty Bundle',
            'Unit Bundle',
            'Qty Goods',
            'Unit Goods',
            'Weight Nett (KG)',
            'Weight Gross (KG)',
            'Price',
            'Currency',
            'Total Price',
            'Notes (Item)',
        ];
    }

    public function map($item): array
    {
        $arrival = $this->arrival;
        $part = $item->part;

        return [
            $arrival->arrival_no,
            $arrival->invoice_no,
            optional($arrival->invoice_date)->format('Y-m-d') ?? '-',
            $arrival->vendor->vendor_name ?? '-',
            $arrival->vessel ?? '-',
            $arrival->trucking->company_name ?? ($arrival->trucking_company ?: '-'),
            optional($arrival->ETD)->format('Y-m-d') ?? '-',
            optional($arrival->ETA)->format('Y-m-d') ?? '-',
            optional($arrival->ETA_GCI)->format('Y-m-d') ?? '-',
            $arrival->port_of_loading ?? '-',
            $arrival->bill_of_lading ?? '-',
            strtoupper((string)($arrival->bill_of_lading_status ?? '-')),
            $arrival->hs_code ?? '-',
            $arrival->hs_codes ?? ($arrival->hs_code ?? '-'),
            $arrival->container_numbers ?? '-',
            $arrival->seal_code ?? '-',
            $arrival->notes ?? '-',
            $part->part_no ?? '-',
            $part->part_name_gci ?? '-',
            $part->part_name_vendor ?? '-',
            $item->size ?? '-',
            $item->material_group ?? '-',
            (float)($item->qty_bundle ?? 0),
            $item->unit_bundle ?? '-',
            (float)($item->qty_goods ?? 0),
            $item->unit_goods ?? '-',
            (float)($item->weight_nett ?? 0),
            (float)($item->weight_gross ?? 0),
            (float)($item->price ?? 0),
            $arrival->currency ?? 'USD',
            (float)($item->total_price ?? 0),
            $item->notes ?? '-',
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
            'A' => 15, // Arrival No
            'B' => 15, // Invoice No
            'C' => 12, // Invoice Date
            'D' => 25, // Vendor
            'E' => 15, // Vessel
            'F' => 20, // Trucking
            'G' => 12, // ETD
            'H' => 12, // ETA JKT
            'I' => 12, // ETA GCI
            'J' => 20, // Port of Loading
            'K' => 15, // Bill of Lading
            'L' => 12, // BL Status
            'M' => 15, // Primary HS Code
            'N' => 25, // All HS Codes
            'O' => 25, // Containers
            'P' => 15, // Seal Code
            'Q' => 25, // Notes (Shipment)
            'R' => 15, // Part No
            'S' => 25, // Part Name (GCI)
            'T' => 25, // Part Name (Vendor)
            'U' => 20, // Size
            'V' => 20, // Material Group
            'W' => 12, // Qty Bundle
            'X' => 12, // Unit Bundle
            'Y' => 12, // Qty Goods
            'Z' => 12, // Unit Goods
            'AA' => 15, // Weight Nett
            'AB' => 15, // Weight Gross
            'AC' => 12, // Price
            'AD' => 10, // Currency
            'AE' => 15, // Total Price
            'AF' => 25, // Notes (Item)
        ];
    }
}
