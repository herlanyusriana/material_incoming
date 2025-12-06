<?php

namespace App\Exports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VendorsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return Vendor::orderBy('vendor_name')->get();
    }

    public function headings(): array
    {
        return [
            'vendor_name',
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
            'B' => 25,
            'C' => 30,
            'D' => 20,
            'E' => 40,
            'F' => 25,
            'G' => 12,
        ];
    }
}
