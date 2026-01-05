<?php

namespace App\Imports;

use App\Models\Part;
use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;

class PartsImport implements ToModel, WithHeadingRow, WithValidation
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
        foreach (['part_no', 'part_number', 'register_no', 'register_number', 'size', 'hs_code'] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $raw = trim((string) ($data[$key] ?? ''));
            $data[$key] = $raw === '' ? null : strtoupper($raw);
        }

        foreach (['part_name_vendor', 'vendor_part_name', 'part_name_gci', 'part_name_internal', 'gci_part_name'] as $key) {
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
        $vendorId = $this->firstNonEmpty($row, ['vendor_id']);
        $vendorName = $this->firstNonEmpty($row, ['vendor', 'vendor_name']);

        $vendor = null;
        if ($vendorId !== null && is_numeric($vendorId)) {
            $vendor = Vendor::find((int) $vendorId);
        }

        if (!$vendor && $vendorName) {
            $normalized = mb_strtolower(trim($vendorName));
            $vendor = Vendor::whereRaw('LOWER(vendor_name) = ?', [$normalized])->first();
        }

        if (!$vendor) {
            return null;
        }

        $partNo = $this->firstNonEmpty($row, ['part_no', 'part_number']);
        $registerNo = $this->firstNonEmpty($row, ['register_no', 'register_number', 'size']);
        $partNameVendor = $this->firstNonEmpty($row, ['part_name_vendor', 'vendor_part_name']);
        $partNameGci = $this->firstNonEmpty($row, ['part_name_gci', 'part_name_internal', 'gci_part_name']);
        $hsCode = $this->firstNonEmpty($row, ['hs_code']);

        $statusRaw = $this->firstNonEmpty($row, ['status']);
        $status = $statusRaw ? mb_strtolower(trim($statusRaw)) : 'active';
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        if (!$partNameVendor) {
            $partNameVendor = $partNo;
        }

        if (!$partNameGci) {
            $partNameGci = $partNameVendor ?: $partNo;
        }

        return new Part([
            'part_no' => $partNo,
            'register_no' => $registerNo ?: $partNo,
            'part_name_vendor' => $partNameVendor,
            'part_name_gci' => $partNameGci,
            'hs_code' => $hsCode,
            'vendor_id' => $vendor->id,
            'status' => $status,
        ]);
    }

    public function rules(): array
    {
        return [
            'vendor' => 'required_without:vendor_id|string|max:255',
            'vendor_id' => 'nullable|integer',
            'part_no' => ['required_without:part_number', 'nullable', 'string', 'max:255', Rule::unique('parts', 'part_no')],
            'part_number' => ['required_without:part_no', 'nullable', 'string', 'max:255', Rule::unique('parts', 'part_no')],
            'register_no' => 'nullable|string|max:255',
            'register_number' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
        ];
    }
}
