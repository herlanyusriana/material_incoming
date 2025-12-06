<?php

namespace App\Imports;

use App\Models\Part;
use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PartsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // Find vendor by name
        $vendor = Vendor::where('vendor_name', $row['vendor'])->first();
        
        if (!$vendor) {
            return null;
        }

        return new Part([
            'part_no' => $row['part_number'],
            'part_name_vendor' => $row['part_name_vendor'] ?? $row['part_number'],
            'part_name_gci' => $row['part_name_internal'] ?? $row['part_name_vendor'] ?? $row['part_number'],
            'register_no' => $row['part_number'],
            'vendor_id' => $vendor->id,
            'description' => $row['description'] ?? null,
            'status' => strtolower($row['status'] ?? 'active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'part_number' => 'required|string|max:255',
            'vendor' => 'required|string',
        ];
    }
}
