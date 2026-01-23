<?php

namespace App\Imports;

use App\Models\Part;
use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class PartsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnFailure
{
    use SkipsFailures;

    /** @var array<int, string> */
    private array $createdVendors = [];
    public $duplicates = [];
    private $confirm = false;

    public function __construct($confirm = false)
    {
        $this->confirm = (bool) $confirm;
    }

    /** @var array<int, string> */
    private array $vendorTypeKeys = [
        'vendor_type',
        'vendor type',
        'vendortype',
        'supplier_type',
        'supplier type',
        'suppliertype',
    ];

    public function createdVendors(): array
    {
        return array_values(array_unique($this->createdVendors));
    }

    private function normalizeVendorType(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = mb_strtolower(trim($value));
        return in_array($raw, ['import', 'local', 'tolling'], true) ? $raw : null;
    }

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

        // Resolving Part Number aliases
        $partNoResolved = $this->firstNonEmpty($data, [
            'part_no', 'part_number',
            'gci_part_no', 'gci_part_number',
            'item_no', 'item_number',
            'material_no', 'material_number',
            'product_no', 'product_number',
            'part',
            'code', 'item_code'
        ]);

        if ($partNoResolved !== null) {
            $data['part_no'] = $partNoResolved;
        }

        foreach (['part_no', 'part_number', 'register_no', 'register_number', 'size', 'hs_code'] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $raw = trim((string) ($data[$key] ?? ''));
            $data[$key] = $raw === '' ? null : strtoupper($raw);
        }

        $vendorTypeResolved = $this->firstNonEmpty($data, $this->vendorTypeKeys);
        if ($vendorTypeResolved !== null) {
            $data['vendor_type'] = $this->normalizeVendorType($vendorTypeResolved);
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

    public function collection(\Illuminate\Support\Collection $rows)
    {
        // Phase 2: Process and Save (Now handles Update or Create)
        foreach ($rows as $row) {
            // Fix: Convert Collection to array
            $row = $row instanceof \Illuminate\Support\Collection ? $row->toArray() : (array) $row;
            $this->processRow($row);
        }
    }

    private function processRow(array $row)
    {
        $vendorId = $this->firstNonEmpty($row, ['vendor_id']);
        $vendorName = $this->firstNonEmpty($row, ['vendor', 'vendor_name']);
        $vendorTypeRaw = $this->firstNonEmpty($row, $this->vendorTypeKeys);
        $vendorType = $this->normalizeVendorType($vendorTypeRaw);

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
            $this->createdVendors[] = $vendor->vendor_name . ' (' . strtoupper($vendor->vendor_type ?? 'import') . ')';
        }

        if (!$vendor) {
            return;
        }

        if ($vendorType !== null && (!$vendor->vendor_type || trim((string) $vendor->vendor_type) === '')) {
            $vendor->vendor_type = $vendorType;
            $vendor->save();
        }

        $partNo = $this->firstNonEmpty($row, ['part_no', 'part_number']);
        if (!$partNo) {
            return;
        }
        $registerNo = $this->firstNonEmpty($row, ['register_no', 'register_number', 'size']);
        $partNameVendor = $this->firstNonEmpty($row, ['part_name_vendor', 'vendor_part_name']);
        $partNameGci = $this->firstNonEmpty($row, ['part_name_gci', 'part_name_internal', 'gci_part_name']);
        $hsCode = $this->firstNonEmpty($row, ['hs_code']);
        $qualityInspectionRaw = $this->firstNonEmpty($row, ['quality_inspection', 'qc_inspection', 'quality']);
        $qualityInspection = null;
        if ($qualityInspectionRaw !== null) {
            $flag = strtoupper(trim((string) $qualityInspectionRaw));
            if (in_array($flag, ['YES', 'Y', '1', 'TRUE'], true)) {
                $qualityInspection = 1;
            } else {
                 $qualityInspection = 0;
            }
        }

        // Check if exists
        $existingPart = Part::where('part_no', $partNo)
            ->where('vendor_id', $vendor->id)
            ->first();

        if ($existingPart) {
            // Already exists for this vendor
            $this->duplicates[] = "{$partNo} [{$vendor->vendor_name}]";
            return;
        }

        $part = new Part();
        $part->part_no = $partNo;
        $part->vendor_id = $vendor->id;

        if ($registerNo !== null) {
            $part->register_no = $registerNo;
        }

        if ($partNameVendor !== null) {
            $part->part_name_vendor = $partNameVendor;
        }

        if ($partNameGci !== null) {
            $part->part_name_gci = $partNameGci;
        }

        if ($hsCode !== null) {
            $part->hs_code = $hsCode;
        }

        if ($qualityInspection !== null) {
            $part->quality_inspection = $qualityInspection;
        }

        $price = $this->firstNonEmpty($row, ['price', 'cost']);
        if ($price !== null && is_numeric($price)) {
            $part->price = (float) $price;
        }

        $uom = $this->firstNonEmpty($row, ['uom', 'unit', 'unit_of_measure']);
        if ($uom !== null) {
            $part->uom = strtoupper(trim((string) $uom));
        }

        if (array_key_exists('status', $row)) {
            $statusRaw = $this->firstNonEmpty($row, ['status']);
            $status = $statusRaw ? mb_strtolower(trim($statusRaw)) : null; 
            if ($status) {
                 if (!in_array($status, ['active', 'inactive'], true)) {
                    $status = 'active';
                }
                $part->status = $status;
            }
        } else {
             $part->status = 'active';
        }

        if (!$part->register_no || trim((string) $part->register_no) === '') {
            $part->register_no = $partNo;
        }
        if (!$part->part_name_vendor || trim((string) $part->part_name_vendor) === '') {
            $part->part_name_vendor = $partNo;
        }
        if (!$part->part_name_gci || trim((string) $part->part_name_gci) === '') {
            $part->part_name_gci = $part->part_name_vendor ?: $partNo;
        }
        $part->save();
    }

    public function rules(): array
    {
        return [
            'vendor' => 'required_without_all:vendor_id,vendor_name|string|max:255',
            'vendor_name' => 'required_without_all:vendor_id,vendor|string|max:255',
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'vendor_type' => ['nullable', Rule::in(['import', 'local', 'tolling'])],
            'part_no' => ['required_without:part_number', 'nullable', 'string', 'max:255'],
            'part_number' => ['required_without:part_no', 'nullable', 'string', 'max:255'],
            'register_no' => 'nullable|string|max:255',
            'register_number' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'quality_inspection' => 'nullable',
            'price' => 'nullable|numeric|min:0',
            'uom' => 'nullable|string|max:20',
        ];
    }
}
