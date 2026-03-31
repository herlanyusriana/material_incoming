<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportDocumentRecapExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, ShouldAutoSize
{
    public function __construct(private readonly Collection $arrivals)
    {
    }

    public function collection(): Collection
    {
        return $this->arrivals;
    }

    public function headings(): array
    {
        return [
            'Transaction No',
            'Vendor',
            'Invoice No',
            'Invoice Date',
            'No PEN',
            'Tanggal No PEN',
            'No AJU',
        ];
    }

    public function map($arrival): array
    {
        return [
            $arrival->transaction_no ?: '-',
            $arrival->vendor->vendor_name ?? '-',
            $arrival->invoice_no ?: '-',
            optional($arrival->invoice_date)->format('Y-m-d') ?: '-',
            $arrival->pen_no ?: '-',
            optional($arrival->pen_date)->format('Y-m-d') ?: '-',
            $arrival->aju_no ?: '-',
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
            'A' => 18,
            'B' => 34,
            'C' => 22,
            'D' => 16,
            'E' => 22,
            'F' => 16,
            'G' => 26,
        ];
    }
}
