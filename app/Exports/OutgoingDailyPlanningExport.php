<?php

namespace App\Exports;

use App\Models\OutgoingDailyPlan;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OutgoingDailyPlanningExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(private readonly OutgoingDailyPlan $plan)
    {
    }

    public function headings(): array
    {
        $headings = ['No', 'LINE', 'CUSTOMER PART NAME', 'CUSTOMER PART NO'];
        foreach ($this->days() as $d) {
            $date = $d->format('Y-m-d');
            $headings[] = "{$date} Seq";
            $headings[] = "{$date} Qty";
        }
        return $headings;
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $days = $this->days();
        $rows = $this->plan->rows()->with(['cells', 'customerPart'])->get();

        $out = [];
        foreach ($rows as $row) {
            $cellsByDate = $row->cells->keyBy(fn($c) => $c->plan_date->format('Y-m-d'));

            $partName = $row->customerPart->customer_part_name ?? $row->part_name ?? '';
            // Append case name if exists
            if (!empty($row->customerPart->case_name)) {
                $partName .= ' ' . $row->customerPart->case_name;
            }

            $line = [
                $row->row_no,
                $row->production_line,
                $partName,
                $row->customerPart->customer_part_no ?? $row->part_no,
            ];
            foreach ($days as $d) {
                $key = $d->format('Y-m-d');
                $cell = $cellsByDate->get($key);
                $line[] = $cell?->seq ?? '-';
                $line[] = $cell?->qty ?? '-';
            }
            $out[] = $line;
        }

        return $out;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /** @return list<Carbon> */
    private function days(): array
    {
        $days = [];
        $cursor = Carbon::parse($this->plan->date_from)->copy()->startOfDay();
        $end = Carbon::parse($this->plan->date_to)->copy()->startOfDay();
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
