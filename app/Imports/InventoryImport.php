<?php

namespace App\Imports;

use App\Models\Inventory;
use App\Models\Part;
use Illuminate\Validation\Rule;
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
        $partId = $this->firstNonEmpty($row, ['part_id']);

        $part = null;
        if ($partId !== null && is_numeric($partId)) {
            $part = Part::find((int) $partId);
        }

        if (!$part && $partNo) {
            $part = Part::where('part_no', $partNo)->first();
        }

        if (!$part) {
            return null;
        }

        $onHandRaw = $this->firstNonEmpty($row, ['on_hand']);
        $onOrderRaw = $this->firstNonEmpty($row, ['on_order']);
        $asOfDate = $this->firstNonEmpty($row, ['as_of_date']);

        $onHand = $onHandRaw !== null ? (float) $onHandRaw : 0;
        $onOrder = $onOrderRaw !== null ? (float) $onOrderRaw : 0;

        Inventory::updateOrCreate(
            ['part_id' => $part->id],
            [
                'on_hand' => $onHand,
                'on_order' => $onOrder,
                'as_of_date' => $asOfDate ?: null,
            ]
        );

        return null;
    }

    public function rules(): array
    {
        return [
            'part_no' => ['required_without:part_id', 'nullable', 'string', 'max:255'],
            'part_number' => ['required_without:part_id', 'nullable', 'string', 'max:255'],
            'part_id' => ['required_without_all:part_no,part_number', 'nullable', 'integer', Rule::exists('parts', 'id')],
            'on_hand' => 'nullable|numeric|min:0',
            'on_order' => 'nullable|numeric|min:0',
            'as_of_date' => 'nullable|date',
        ];
    }
}
