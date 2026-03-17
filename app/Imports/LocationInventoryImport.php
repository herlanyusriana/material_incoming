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
    public int $created = 0;

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
        $targetQty = (float) ($row['qty'] ?? $row['qty_on_hand'] ?? 0);

        if (!$partNo || !$locationCode) {
            $this->skipped++;
            return null;
        }

        if ($targetQty < 0) {
            $this->skipped++;
            return null;
        }

        // Find or create GciPart
        $part = GciPart::where('part_no', $partNo)->first();
        if (!$part) {
            $partName = $row['part_name'] ?? null;
            if (!$partName) {
                $this->skipped++;
                return null;
            }

            $part = GciPart::create([
                'part_no' => $partNo,
                'part_name' => $partName,
                'model' => $row['model'] ?? null,
                'classification' => $row['classification'] ?? 'fg',
                'status' => 'active',
                'default_location' => $row['default_location'] ?? $locationCode,
            ]);
            $this->created++;
        }

        $batchNo = $row['batch_no'] ?? $row['batch'] ?? null;

        // Get current stock at this exact location+batch combo
        $current = LocationInventory::where('gci_part_id', $part->id)
            ->where('location_code', strtoupper(trim($locationCode)))
            ->where('batch_no', $batchNo)
            ->first();

        $currentQty = $current ? (float) $current->qty_on_hand : 0;
        $delta = $targetQty - $currentQty;

        // Skip if nothing to change
        if (abs($delta) < 0.0001) {
            $this->skipped++;
            return null;
        }

        LocationInventory::updateStock(null, $locationCode, $delta, $batchNo, null, $part->id, 'IMPORT', 'Excel import');

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