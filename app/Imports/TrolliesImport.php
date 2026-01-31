<?php

namespace App\Imports;

use App\Models\Trolly;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TrolliesImport implements ToModel, WithHeadingRow, WithValidation
{
    public function prepareForValidation(array $data, int $index): array
    {
        foreach (['code', 'type', 'kind', 'status'] as $key) {
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
        $code = trim((string) ($row['code'] ?? ''));
        if ($code === '') {
            return null;
        }

        $type = array_key_exists('type', $row) ? trim((string) $row['type']) : null;
        $kind = array_key_exists('kind', $row) ? trim((string) $row['kind']) : null;
        $status = array_key_exists('status', $row) ? trim((string) $row['status']) : 'ACTIVE';

        $type = $type === '' ? null : strtoupper($type);
        $kind = $kind === '' ? null : strtoupper($kind);
        $status = $status === '' || $status === null ? 'ACTIVE' : strtoupper($status);

        Trolly::updateOrCreate(
            ['code' => strtoupper($code)],
            [
                'type' => $type,
                'kind' => $kind,
                'status' => $status,
                'qr_payload' => Trolly::buildPayload($code, $type, $kind),
            ]
        );

        return null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:50'],
            'kind' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
        ];
    }
}
