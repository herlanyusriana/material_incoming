<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OutgoingDailyPlanningTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function __construct(
        private readonly Carbon $dateFrom,
        private readonly Carbon $dateTo,
    ) {
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        // Example content
        $days = $this->days();
        $first = $days[0] ?? null;

        return [
            array_merge(
                [1, 'LG ELECTRONICS', 'NR1', 'VT 12', 'GN-B242P5SF.ASTCNA0'],
                $first ? [140] : [],
            ),
        ];
    }

    public function headings(): array
    {
        // New Column Structure: No, Customer Name, Line, Project Name, Customer Part No
        $headings = ['No', 'CUSTOMER NAME', 'LINE', 'PROJECT NAME', 'CUSTOMER PART NO'];
        foreach ($this->days() as $d) {
            $date = $d->format('Y-m-d');
            // Simplified to just Qty per date usually, but keeping compatible just in case user wants seq
            // Based on user screenshot, it's just "Plan Qty"
            $headings[] = "{$date} Qty";
        }
        return $headings;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        // Basic widths; day columns will auto-fit in Excel anyway.
        $widths = [
            'A' => 5,  // No
            'B' => 30, // Customer Name
            'C' => 10, // Line
            'D' => 20, // Project Name
            'E' => 30, // Cust Part No
        ];
        return $widths;
    }

    /** @return list<Carbon> */
    private function days(): array
    {
        $days = [];
        $cursor = $this->dateFrom->copy()->startOfDay();
        $end = $this->dateTo->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $days[] = $cursor->copy();
            $cursor->addDay();
            if (count($days) > 31) {
                break;
            }
        }
        return $days;
    }
}
