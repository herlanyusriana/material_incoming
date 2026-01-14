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
        $days = $this->days();
        $first = $days[0] ?? null;

        return [
            array_merge(
                [1, 'NR1', 'GN-304SLBR.ADSPEIN'],
                $first ? [1, 6] : [],
            ),
        ];
    }

    public function headings(): array
    {
        $headings = ['No', 'production_line', 'part_no'];
        foreach ($this->days() as $d) {
            $date = $d->format('Y-m-d');
            $headings[] = "{$date} Seq";
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
            'A' => 6,
            'B' => 16,
            'C' => 24,
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

