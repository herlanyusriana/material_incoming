<?php

namespace App\Exports;

use App\Models\CustomerPlanningImport;
use App\Models\CustomerPlanningRow;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerPlanningImportRowsExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function __construct(
        private readonly int $importId,
    ) {
    }

    public function array(): array
    {
        $import = CustomerPlanningImport::query()->with('customer')->find($this->importId);
        $customerCode = $import?->customer?->code ?? '';

        $rows = CustomerPlanningRow::query()
            ->with('part')
            ->where('import_id', $this->importId)
            ->orderBy('id')
            ->get();

        return $rows->map(function ($row) use ($customerCode) {
            return [
                $this->importId,
                $customerCode,
                $row->customer_part_no ?? '',
                $row->minggu ?? '',
                $row->qty !== null ? (string) $row->qty : '',
                $row->part?->part_no ?? '',
                $row->row_status ?? '',
                $row->error_message ?? '',
            ];
        })->all();
    }

    public function headings(): array
    {
        return [
            'Import ID',
            'Customer Code',
            'Customer Part No',
            'Minggu',
            'Qty',
            'Auto Part GCI',
            'Row Status',
            'Error Message',
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
            'A' => 10,
            'B' => 16,
            'C' => 22,
            'D' => 12,
            'E' => 12,
            'F' => 18,
            'G' => 16,
            'H' => 30,
        ];
    }
}

