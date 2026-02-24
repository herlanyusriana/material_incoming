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
        return [
            'classification',
            'part_no',
            'part_name',
            'model',
            'customer',
            'status',
            'vendor',
            'vendor_part_no',
            'vendor_part_name',
            'register_no',
            'price',
            'uom',
            'hs_code',
            'quality_inspection',
            'vendor_status',
        ];
    }

    public function map($part): array
    {
        $rows = [];

        // If part has vendor links (RM), output one row per vendor link
        if ($part->vendorLinks->count() > 0) {
            foreach ($part->vendorLinks as $vl) {
                $rows[] = [
                    $part->classification,
                    $part->part_no,
                    $part->part_name ?? '',
                    $part->model ?? '',
                    $part->customer->name ?? '',
                    $part->status ?? 'active',
                    $vl->vendor->vendor_name ?? '',
                    $vl->vendor_part_no ?? '',
                    $vl->vendor_part_name ?? '',
                    $vl->register_no ?? '',
                    $vl->price ?? 0,
                    $vl->uom ?? '',
                    $vl->hs_code ?? '',
                    $vl->quality_inspection ? 'YES' : '-',
                    $vl->status ?? 'active',
                ];
            }
        } else {
            // FG/WIP or RM with no vendor — output single row
            $rows[] = [
                $part->classification,
                $part->part_no,
                $part->part_name ?? '',
                $part->model ?? '',
                $part->customer->name ?? '',
                $part->status ?? 'active',
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
        return [
            'A' => 14,
            'B' => 22,
            'C' => 30,
            'D' => 14,
            'E' => 22,
            'F' => 10,
            'G' => 25,
            'H' => 22,
            'I' => 30,
            'J' => 18,
            'K' => 12,
            'L' => 10,
            'M' => 14,
            'N' => 18,
            'O' => 12,
        ];
    }
}
