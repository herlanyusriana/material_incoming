<?php

namespace App\Imports;

use App\Models\GciPart;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class GciPartsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use SkipsFailures;

    private function norm(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = str_replace("\u{00A0}", ' ', (string) $value);
        $str = preg_replace('/\s+/', ' ', $str) ?? $str;
        $str = trim($str);
        return $str === '' ? null : $str;
    }

    public function prepareForValidation(array $data, int $index): array
    {
        foreach (['part_no', 'part_number'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = strtoupper((string) ($this->norm($data[$key]) ?? ''));
            }
        }

        foreach (['part_name', 'part_name_gci'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->norm($data[$key]);
            }
        }

        if (array_key_exists('model', $data)) {
            $data['model'] = $this->norm($data['model']);
        }

        if (array_key_exists('classification', $data)) {
            $cls = strtoupper((string) ($this->norm($data['classification']) ?? ''));
            $data['classification'] = in_array($cls, ['FG', 'RM', 'WIP'], true) ? $cls : 'FG';
        }

        if (array_key_exists('status', $data)) {
            $status = strtolower((string) ($this->norm($data['status']) ?? ''));
            $data['status'] = in_array($status, ['active', 'inactive'], true) ? $status : 'active';
        }

        return $data;
    }

    public function model(array $row)
    {
        $partNo = $this->norm($row['part_no'] ?? $row['part_number'] ?? null);
        $partNo = $partNo !== null ? strtoupper($partNo) : null;
        if (!$partNo) {
            return null;
        }

        $partName = $this->norm($row['part_name'] ?? $row['part_name_gci'] ?? null);
        $model = $this->norm($row['model'] ?? null);
        $classification = strtoupper((string) ($this->norm($row['classification'] ?? null) ?? 'FG'));
        if (!in_array($classification, ['FG', 'RM', 'WIP'], true)) {
            $classification = 'FG';
        }
        $status = strtolower((string) ($this->norm($row['status'] ?? null) ?? 'active'));
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        GciPart::updateOrCreate(
            ['part_no' => $partNo],
            [
                'classification' => $classification,
                'part_name' => $partName !== null && $partName !== '' ? $partName : $partNo,
                'model' => $model,
                'status' => $status,
            ],
        );

        return null;
    }

    public function rules(): array
    {
        return [
            'part_no' => ['required_without:part_number', 'nullable', 'string', 'max:100'],
            'part_number' => ['required_without:part_no', 'nullable', 'string', 'max:100'],
            'classification' => ['nullable', Rule::in(['FG', 'RM', 'WIP'])],
            'part_name' => ['nullable', 'string', 'max:255'],
            'part_name_gci' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
