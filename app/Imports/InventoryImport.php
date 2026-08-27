<?php

namespace App\Imports;

use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class InventoryImport implements ToModel, WithHeadingRow, WithValidation
{
    private function firstNonEmpty(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }

            $value = is_string($row[$key]) ? trim($row[$key]) : $row[$key];
            if ($value === null) {
                continue;
            }

            if (is_string($value) && $value === '') {
                continue;
            }

            return (string) $value;
        }

        return null;
    }

    public function prepareForValidation(array $data, int $index): array
    {
        foreach (['part_no', 'part_number'] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $raw = trim((string) ($data[$key] ?? ''));
            $data[$key] = $raw === '' ? null : strtoupper($raw);
        }

        return $data;
    }

    public function model(array $row)
    {
        $partNo = $this->firstNonEmpty($row, ['part_no', 'part_number']);

        $part = null;
        if ($partNo) {
            $part = GciPart::where('part_no', $partNo)->first();
        }

        if (!$part) {
            return null;
        }

        $onHandRaw = $this->firstNonEmpty($row, ['on_hand']);
        $batchNo = $this->firstNonEmpty($row, ['batch', 'batch_no']);

        $onHand = $onHandRaw !== null ? (float) $onHandRaw : 0;

        if ($onHand > 0) {
            // Use new schema InventoryLocationStock with default location
            $defaultLocation = $part->default_location ?? 'WIP-DEFAULT';
            InventoryLocationStock::updateStock(
                gciPartId: (int) $part->id,
                locationCode: $defaultLocation,
                qtyChange: $onHand,
                batchNo: $batchNo,
                transactionType: 'INVENTORY_IMPORT',
                sourceReference: 'inventory_import',
                createdBy: null
            );
        }

        return null;
    }

    public function rules(): array
    {
        return [
            'part_no' => ['nullable', 'string', 'max:255'],
            'part_number' => ['nullable', 'string', 'max:255'],
            'on_hand' => 'nullable|numeric|min:0',
            'on_order' => 'nullable|numeric|min:0',
            'batch' => 'nullable|string|max:255',
            'batch_no' => 'nullable|string|max:255',
            'as_of_date' => 'nullable|date',
        ];
    }
}
