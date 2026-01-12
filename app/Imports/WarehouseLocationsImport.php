<?php

namespace App\Imports;

use App\Models\WarehouseLocation;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class WarehouseLocationsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function prepareForValidation(array $data, int $index): array
    {
        foreach (['location_code', 'class', 'zone', 'status'] as $key) {
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
        $locationCode = trim((string) ($row['location_code'] ?? ''));
        if ($locationCode === '') {
            return null;
        }

        $class = array_key_exists('class', $row) ? (is_string($row['class']) ? trim($row['class']) : $row['class']) : null;
        $zone = array_key_exists('zone', $row) ? (is_string($row['zone']) ? trim($row['zone']) : $row['zone']) : null;
        $status = array_key_exists('status', $row) ? (is_string($row['status']) ? trim($row['status']) : $row['status']) : null;

        $class = $class === '' ? null : (is_string($class) ? strtoupper($class) : $class);
        $zone = $zone === '' ? null : (is_string($zone) ? strtoupper($zone) : $zone);
        $status = $status === '' || $status === null ? 'ACTIVE' : (is_string($status) ? strtoupper($status) : (string) $status);

        $payload = WarehouseLocation::buildPayload($locationCode, $class, $zone);

        WarehouseLocation::updateOrCreate(
            ['location_code' => strtoupper($locationCode)],
            [
                'class' => $class,
                'zone' => $zone,
                'status' => $status,
                'qr_payload' => $payload,
            ]
        );

        return null;
    }

    public function rules(): array
    {
        return [
            'location_code' => ['required', 'string', 'max:50'],
            'class' => ['nullable', 'string', 'max:50'],
            'zone' => ['nullable', 'string', 'max:50'],
            'qr_payload' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:20'],
        ];
    }
}

