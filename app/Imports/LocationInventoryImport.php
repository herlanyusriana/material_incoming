<?php

namespace App\Imports;

use App\Models\GciPart;
use App\Models\LocationInventory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class LocationInventoryImport implements ToModel, WithHeadingRow, WithValidation
{
    public int $imported = 0;
    public int $skipped = 0;

    public function prepareForValidation(array $data, int $index): array
    {
        foreach (['part_no', 'location', 'location_code'] as $key) {
            if (array_key_exists($key, $data)) {
                $raw = trim((string) ($data[$key] ?? ''));
                $data[$key] = $raw === '' ? null : strtoupper($raw);
            }
        }

        foreach (['batch_no', 'batch'] as $key) {
            if (array_key_exists($key, $data)) {
                $raw = trim((string) ($data[$key] ?? ''));
                $data[$key] = $raw === '' ? null : strtoupper($raw);
            }
        }

        return $data;
    }

    public function model(array $row)
    {
        $partNo = $row['part_no'] ?? null;
        $locationCode = $row['location_code'] ?? $row['location'] ?? null;
        $qty = (float) ($row['qty'] ?? $row['qty_on_hand'] ?? 0);

        if (!$partNo || !$locationCode || $qty <= 0) {
            $this->skipped++;
            return null;
        }

        $part = GciPart::where('part_no', $partNo)->first();
        if (!$part) {
            $this->skipped++;
            return null;
        }

        $batchNo = $row['batch_no'] ?? $row['batch'] ?? null;

        LocationInventory::updateStock(null, $locationCode, $qty, $batchNo, null, $part->id);

        $this->imported++;
        return null;
    }

    public function rules(): array
    {
        return [
            'part_no' => ['nullable', 'string', 'max:255'],
            'location_code' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:50'],
            'qty' => ['nullable', 'numeric', 'min:0'],
            'qty_on_hand' => ['nullable', 'numeric', 'min:0'],
            'batch_no' => ['nullable', 'string', 'max:255'],
            'batch' => ['nullable', 'string', 'max:255'],
        ];
    }
}