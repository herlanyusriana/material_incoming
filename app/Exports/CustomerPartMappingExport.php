<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\CustomerPart;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerPartMappingExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function __construct(
        private readonly mixed $customerId = null,
    ) {
    }

    public function array(): array
    {
        $rows = [];

        $customerParts = CustomerPart::query()
            ->with(['customer', 'components.part'])
            ->when($this->customerId, fn ($q) => $q->where('customer_id', $this->customerId))
            ->orderBy(Customer::select('code')->whereColumn('customers.id', 'customer_parts.customer_id'))
            ->orderBy('customer_part_no')
            ->get();

        foreach ($customerParts as $cp) {
            $components = ($cp->components ?? collect())->sortBy(fn ($c) => $c->part?->part_no ?? '');
            if ($components->isEmpty()) {
                $rows[] = [
                    $cp->customer?->code ?? '',
                    $cp->customer?->name ?? '',
                    $cp->customer_part_no ?? '',
                    $cp->customer_part_name ?? '',
                    $cp->status ?? 'active',
                    '',
                    '',
                    '',
                ];
                continue;
            }

            foreach ($components as $comp) {
                $rows[] = [
                    $cp->customer?->code ?? '',
                    $cp->customer?->name ?? '',
                    $cp->customer_part_no ?? '',
                    $cp->customer_part_name ?? '',
                    $cp->status ?? 'active',
                    $comp->part?->part_no ?? '',
                    $comp->part?->part_name ?? '',
                    $comp->usage_qty !== null ? (string) $comp->usage_qty : '',
                ];
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Customer Code',
            'Customer Name',
            'Customer Part No',
            'Customer Part Name',
            'Status',
            'GCI Part No',
            'GCI Part Name',
            'Usage Qty',
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
            'A' => 16,
            'B' => 24,
            'C' => 22,
            'D' => 28,
            'E' => 10,
            'F' => 18,
            'G' => 28,
            'H' => 12,
        ];
    }
}

