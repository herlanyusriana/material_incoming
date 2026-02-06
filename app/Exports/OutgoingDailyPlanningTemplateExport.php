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

    public function array(): array
    {
        // Example content
        $days = $this->days();
        $first = $days[0] ?? null;

        return [
            array_merge(
                [1, 'NR1', 'COVER ASSY - MODEL X', '123-456-789'],
                $first ? [140] : [],
            ),
        ];
    }

    public function headings(): array
    {
        // New Column Structure: No, Line, Customer Part Name, Customer Part No
        $headings = ['No', 'LINE', 'CUSTOMER PART NAME', 'CUSTOMER PART NO'];
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
            'B' => 10, // Line
            'C' => 30, // Customer Part Name
            'D' => 25, // Customer Part No
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
