<?php

namespace App\Imports;

use App\Models\Part;
use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class PartsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use SkipsFailures;

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
        $vendorResolved = $this->firstNonEmpty($data, [
            'vendor',
            'vendor_name',
            'vendorname',
            'supplier',
            'supplier_name',
            'suppliername',
        ]);

        if ($vendorResolved !== null) {
            $vendorResolved = strtoupper(trim($vendorResolved));
            $data['vendor'] = $vendorResolved;
            $data['vendor_name'] = $vendorResolved;
        }

        foreach (['part_no', 'part_number', 'register_no', 'register_number', 'size', 'hs_code'] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $raw = trim((string) ($data[$key] ?? ''));
            $data[$key] = $raw === '' ? null : strtoupper($raw);
        }

        if (array_key_exists('vendor_type', $data)) {
            $raw = strtolower(trim((string) ($data['vendor_type'] ?? '')));
            $data['vendor_type'] = in_array($raw, ['import', 'local', 'tolling'], true) ? $raw : null;
        }

        if (array_key_exists('quality_inspection', $data)) {
            $raw = strtoupper(trim((string) ($data['quality_inspection'] ?? '')));
            $data['quality_inspection'] = in_array($raw, ['YES', 'Y', '1', 'TRUE'], true) ? 'YES' : null;
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
        $vendorTypeRaw = $this->firstNonEmpty($row, ['vendor_type']);
        $vendorType = $vendorTypeRaw ? mb_strtolower(trim($vendorTypeRaw)) : null;
        if ($vendorType !== null && !in_array($vendorType, ['import', 'local', 'tolling'], true)) {
            $vendorType = null;
        }

        $vendor = null;
        if ($vendorId !== null && is_numeric($vendorId)) {
            $vendor = Vendor::find((int) $vendorId);
        }

        if (!$vendor && $vendorName) {
            $normalized = mb_strtolower(trim($vendorName));
            $vendor = Vendor::whereRaw('LOWER(vendor_name) = ?', [$normalized])->first();
        }

        if (!$vendor && $vendorName) {
            $vendor = Vendor::create([
                'vendor_name' => strtoupper(trim($vendorName)),
                'vendor_type' => $vendorType ?? 'import',
                'status' => 'active',
            ]);
        }

        if (!$vendor) {
            return null;
        }

        if ($vendorType !== null && (!$vendor->vendor_type || trim((string) $vendor->vendor_type) === '')) {
            $vendor->vendor_type = $vendorType;
            $vendor->save();
        }

        $partNo = $this->firstNonEmpty($row, ['part_no', 'part_number']);
        $registerNo = $this->firstNonEmpty($row, ['register_no', 'register_number', 'size']);
        $partNameVendor = $this->firstNonEmpty($row, ['part_name_vendor', 'vendor_part_name']);
        $partNameGci = $this->firstNonEmpty($row, ['part_name_gci', 'part_name_internal', 'gci_part_name']);
        $hsCode = $this->firstNonEmpty($row, ['hs_code']);
        $qualityInspectionRaw = $this->firstNonEmpty($row, ['quality_inspection', 'qc_inspection', 'quality']);
        $qualityInspection = null;
        if ($qualityInspectionRaw !== null) {
            $flag = strtoupper(trim((string) $qualityInspectionRaw));
            if (in_array($flag, ['YES', 'Y', '1', 'TRUE'], true)) {
                $qualityInspection = 'YES';
            }
        }

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
            'quality_inspection' => $qualityInspection,
            'vendor_id' => $vendor->id,
            'status' => $status,
        ]);
    }

    public function rules(): array
    {
        return [
            'vendor' => 'required_without_all:vendor_id,vendor_name|string|max:255',
            'vendor_name' => 'required_without_all:vendor_id,vendor|string|max:255',
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'vendor_type' => ['nullable', Rule::in(['import', 'local', 'tolling'])],
            'part_no' => ['required_without:part_number', 'nullable', 'string', 'max:255', Rule::unique('parts', 'part_no')],
            'part_number' => ['required_without:part_no', 'nullable', 'string', 'max:255', Rule::unique('parts', 'part_no')],
            'register_no' => 'nullable|string|max:255',
            'register_number' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'quality_inspection' => 'nullable',
        ];
    }
}
