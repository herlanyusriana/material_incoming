<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class DeliveryRequirementsExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    private Collection $requirements;
    private string $dateLabel;

    public function __construct(Collection $requirements, string $dateLabel)
    {
        $this->requirements = $requirements;
        $this->dateLabel = $dateLabel;
    }

    public function headings(): array
    {
        return [
            'No',
            'Delivery Date',
            'Category',
            'FG Part Tag',
            'FG Part No.',
            'Model',
            'Cust. Stock',
            'Daily Plan',
            'Requirement',
        ];
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $out = [];
        $no = 1;

        foreach ($this->requirements as $req) {
            $out[] = [
                $no++,
                $req->date?->format('d/m/Y') ?? '-',
                $req->gci_part?->standardPacking?->delivery_class ?? '-',
                $req->customer_part_name ?? '-',
                $req->gci_part?->part_no ?? '-',
                $req->gci_part?->model ?? '-',
                $req->stock_at_customer ?? 0,
                $req->gross_qty ?? 0,
                $req->total_qty ?? 0,
            ];
        }

        // Totals row
        if ($this->requirements->isNotEmpty()) {
            $out[] = [
                '',
                '',
                '',
                '',
                '',
                'TOTALS',
                $this->requirements->sum('stock_at_customer'),
                $this->requirements->sum('gross_qty'),
                $this->requirements->sum('total_qty'),
            ];
        }

        return $out;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 14,
            'C' => 12,
            'D' => 30,
            'E' => 22,
            'F' => 18,
            'G' => 14,
            'H' => 14,
            'I' => 14,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->requirements->count() + 1; // +1 for header
        $totalRow = $lastRow + 1;

        $styles = [
            // Header row
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];

        // Numeric columns right-aligned (G, H, I)
        $sheet->getStyle("G2:I{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G2:I{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');

        // Totals row styling
        if ($this->requirements->isNotEmpty()) {
            $sheet->getStyle("A{$totalRow}:I{$totalRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '1E293B']],
                ],
            ]);
        }

        // All data borders
        $sheet->getStyle("A1:I{$totalRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
            ],
        ]);

        return $styles;
    }
}
