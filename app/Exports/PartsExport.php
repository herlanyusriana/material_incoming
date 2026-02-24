<?php

namespace App\Exports;

use App\Models\GciPart;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PartsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    private ?string $classification;

    public function __construct(?string $classification = null)
    {
        $this->classification = $classification;
    }

    public function collection()
    {
        return GciPart::with(['customer', 'vendorLinks.vendor'])
            ->when($this->classification, fn($q) => $q->where('classification', strtoupper($this->classification)))
            ->orderBy('classification')
            ->orderBy('part_no')
            ->get();
    }

    public function headings(): array
    {
        if (strtoupper($this->classification) === 'RM') {
            return [
                'Classificat',
                'vendor',
                'vendor_type',
                'part_no',
                'size',
                'part_name_vendor',
                'part_name_gci',
                'hs_code',
                'quality_inspection',
                'price',
                'uom',
                'status',
            ];
        }

        return [
            'classification',
            'part_no',
            'part_name',
            'model',
            'customer',
            'status',
        ];
    }

    public function map($part): array
    {
        $rows = [];

        if (strtoupper($this->classification) === 'RM') {
            // RM Export format
            if ($part->vendorLinks->count() > 0) {
                foreach ($part->vendorLinks as $vl) {
                    $rows[] = [
                        $part->classification,
                        $vl->vendor->vendor_name ?? '',
                        $vl->vendor->vendor_type ?? '',
                        $vl->vendor_part_no ?? '',
                        $vl->register_no ?? '',
                        $vl->vendor_part_name ?? '',
                        $part->part_name ?? '',
                        $vl->hs_code ?? '',
                        $vl->quality_inspection ? 'YES' : '-',
                        $vl->price ?? 0,
                        $vl->uom ?? '',
                        $vl->status ?? 'active',
                    ];
                }
            } else {
                $rows[] = [
                    $part->classification,
                    '', // vendor
                    '', // vendor_type
                    '', // part_no (vendor)
                    '', // size / register_no
                    '', // part_name_vendor
                    $part->part_name ?? '', // part_name_gci
                    '', // hs_code
                    '', // quality_inspection
                    0,  // price
                    '', // uom
                    $part->status ?? 'active', // status
                ];
            }
        } else {
            // FG/WIP Export format
            $rows[] = [
                $part->classification,
                $part->part_no,
                $part->part_name ?? '',
                $part->model ?? '',
                $part->customer->name ?? '',
                $part->status ?? 'active',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        if (strtoupper($this->classification) === 'RM') {
            return [
                'A' => 14, // Classificat
                'B' => 25, // vendor
                'C' => 14, // vendor_type
                'D' => 22, // part_no
                'E' => 18, // size
                'F' => 30, // part_name_vendor
                'G' => 30, // part_name_gci
                'H' => 14, // hs_code
                'I' => 18, // quality_inspection
                'J' => 12, // price
                'K' => 10, // uom
                'L' => 12, // status
            ];
        }

        return [
            'A' => 14, // classification
            'B' => 22, // part_no
            'C' => 30, // part_name
            'D' => 22, // model
            'E' => 22, // customer
            'F' => 12, // status
        ];
    }
}
