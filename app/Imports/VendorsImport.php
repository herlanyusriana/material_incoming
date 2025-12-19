<?php

namespace App\Imports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class VendorsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new Vendor([
            'vendor_name' => $row['vendor_name'],
            'country_code' => isset($row['country_code']) ? strtoupper(trim((string) $row['country_code'])) : null,
            'contact_person' => $row['contact_person'] ?? null,
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'address' => $row['address'] ?? null,
            'bank_account' => $row['bank_account'] ?? null,
            'status' => strtolower($row['status'] ?? 'active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'vendor_name' => 'required|string|max:255',
            'country_code' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
        ];
    }
}
