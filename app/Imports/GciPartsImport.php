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
            $class = strtoupper((string) ($this->norm($data['classification']) ?? ''));
            $data['classification'] = in_array($class, ['FG', 'WIP', 'RM'], true) ? $class : 'FG';
        }

        if (array_key_exists('status', $data)) {
            $status = strtolower((string) ($this->norm($data['status']) ?? ''));
            $data['status'] = in_array($status, ['active', 'inactive'], true) ? $status : 'active';
        }

        if (array_key_exists('customer', $data)) {
            $data['customer'] = $this->norm($data['customer']);
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
        $status = strtolower((string) ($this->norm($row['status'] ?? null) ?? ''));
        $customerCode = $this->norm($row['customer'] ?? null);

        // Find existing or create new
        $part = GciPart::where('part_no', $partNo)->first() ?: new GciPart(['part_no' => $partNo]);

        // Use classification from Excel, default to FG for new parts
        $classification = strtoupper((string) ($this->norm($row['classification'] ?? null) ?? ''));
        if (in_array($classification, ['FG', 'WIP', 'RM'], true)) {
            $part->classification = $classification;
        } elseif (!$part->exists) {
            $part->classification = 'FG'; // Default for NEW records only
        }
        // For existing parts without classification in Excel, keep current value

        // Only update if value is provided in Excel
        if ($partName !== null) {
            $part->part_name = $partName;
        } elseif (!$part->exists) {
            $part->part_name = $partNo; // Default for NEW records
        }

        if ($model !== null) {
            $part->model = $model;
        }

        if ($status !== '' && in_array($status, ['active', 'inactive'], true)) {
            $part->status = $status;
        } elseif (!$part->exists) {
            $part->status = 'active'; // Default for NEW records
        }

        if ($customerCode !== null) {
            $customer = \App\Models\Customer::where('name', $customerCode)->first();
            if ($customer) {
                $part->customer_id = $customer->id;
            } else {
                $part->customer_id = null;
            }
        }

        $part->save();

        return null;
    }

    public function rules(): array
    {
        return [
            'part_no' => ['required_without:part_number', 'nullable', 'string', 'max:100'],
            'part_number' => ['required_without:part_no', 'nullable', 'string', 'max:100'],
            'classification' => ['nullable', Rule::in(['FG', 'WIP', 'RM'])],
            'part_name' => ['nullable', 'string', 'max:255'],
            'part_name_gci' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'customer' => ['nullable', 'string', 'max:100'],
        ];
    }
}
