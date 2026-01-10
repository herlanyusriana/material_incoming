<?php

namespace App\Exports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VendorsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return Vendor::query()
            ->select([
                'vendor_name',
                'vendor_type',
                'country_code',
                'contact_person',
                'email',
                'phone',
                'address',
                'bank_account',
                'status',
            ])
            ->orderBy('vendor_name');
    }

    public function headings(): array
    {
        return [
            'vendor_name',
            'vendor_type',
            'country_code',
            'contact_person',
            'email',
            'phone',
            'address',
            'bank_account',
            'status',
        ];
    }

    public function map($vendor): array
    {
        return [
            $vendor->vendor_name,
            $vendor->vendor_type ?? 'import',
            $vendor->country_code,
            $vendor->contact_person,
            $vendor->email,
            $vendor->phone,
            $vendor->address,
            $vendor->bank_account,
            $vendor->status,
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
            'A' => 30,
            'B' => 12,
            'C' => 12,
            'D' => 25,
            'E' => 30,
            'F' => 20,
            'G' => 40,
            'H' => 25,
            'I' => 12,
        ];
    }
}
