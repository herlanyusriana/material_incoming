<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerPart;
use App\Models\CustomerPartComponent;
use App\Models\GciPart;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CustomerPartMappingImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use SkipsFailures;

    private function firstNonEmpty(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }

            $value = $row[$key];
            if (is_string($value)) {
                $value = trim($value);
            }

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

    private function normalizeUpper(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        // Normalize NBSP and collapse whitespace so matching works across Excel exports.
        $value = str_replace("\u{00A0}", ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = trim($value);
        return $value === '' ? null : strtoupper($value);
    }

    private function ensureGciPart(string $partNo, ?string $partName = null): GciPart
    {
        $partNo = $this->normalizeUpper($partNo) ?? '';
        $partName = $partName !== null ? trim($partName) : null;

        $existing = GciPart::query()->where('part_no', $partNo)->first();
        if ($existing) {
            if ($partName !== null && $partName !== '' && ($existing->part_name === null || trim((string) $existing->part_name) === '')) {
                $existing->update(['part_name' => $partName]);
            }
            return $existing;
        }

        return GciPart::create([
            'part_no' => $partNo,
            'part_name' => ($partName !== null && $partName !== '') ? $partName : $partNo,
            'status' => 'active',
        ]);
    }

    public function prepareForValidation(array $data, int $index): array
    {
        $customerCode = $this->firstNonEmpty($data, ['customer_code', 'customer', 'customerid']);
        if ($customerCode !== null) {
            $data['customer_code'] = $this->normalizeUpper($customerCode);
        }

        $customerPartNo = $this->firstNonEmpty($data, ['customer_part_no', 'customer_part', 'customer_part_number']);
        if ($customerPartNo !== null) {
            $data['customer_part_no'] = $this->normalizeUpper($customerPartNo);
        }

        $gciPartNo = $this->firstNonEmpty($data, ['gci_part_no', 'part_gci', 'part_no', 'gci_part_number']);
        if ($gciPartNo !== null) {
            $data['gci_part_no'] = $this->normalizeUpper($gciPartNo);
        }

        $status = $this->firstNonEmpty($data, ['status']);
        if ($status !== null) {
            $status = strtolower(trim($status));
            $data['status'] = in_array($status, ['active', 'inactive'], true) ? $status : 'active';
        }

        return $data;
    }

    public function model(array $row)
    {
        $customerCode = $this->normalizeUpper($this->firstNonEmpty($row, ['customer_code', 'customer']));
        $customerPartNo = $this->normalizeUpper($this->firstNonEmpty($row, [
            'customer_part_no',
            'customer_part',
            'customer_part_number',
            'part_number',
            'part number',
            'customer_part_no_',
        ]));
        $customerPartName = $this->firstNonEmpty($row, ['customer_part_name']);
        $status = $this->firstNonEmpty($row, ['status']);
        $status = $status ? strtolower(trim($status)) : 'active';
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        if (!$customerCode || !$customerPartNo) {
            return null;
        }

        $customer = Customer::query()->where('code', $customerCode)->first();
        if (!$customer) {
            return null;
        }

        $customerPart = CustomerPart::updateOrCreate(
            ['customer_id' => $customer->id, 'customer_part_no' => $customerPartNo],
            [
                'customer_part_name' => $customerPartName !== null && trim($customerPartName) !== '' ? trim($customerPartName) : null,
                'status' => $status,
            ],
        );

        $gciPartNo = $this->normalizeUpper($this->firstNonEmpty($row, ['gci_part_no', 'part_gci', 'part_no', 'gci_part_number']));
        if (!$gciPartNo) {
            return null;
        }

        $gciPartName = $this->firstNonEmpty($row, ['gci_part_name', 'part_name']);
        $gciPart = $this->ensureGciPart($gciPartNo, $gciPartName);

        $usageQtyRaw = $this->firstNonEmpty($row, ['usage_qty', 'usage', 'consumption']);
        $usageQty = $usageQtyRaw !== null && is_numeric($usageQtyRaw) ? (float) $usageQtyRaw : null;
        if ($usageQty === null) {
            return null;
        }

        CustomerPartComponent::updateOrCreate(
            ['customer_part_id' => $customerPart->id, 'part_id' => $gciPart->id],
            ['usage_qty' => $usageQty],
        );

        return null;
    }

    public function rules(): array
    {
        return [
            'customer_code' => ['required', 'string', 'max:50', Rule::exists('customers', 'code')],
            'customer_part_no' => ['required', 'string', 'max:100'],
            'customer_part_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'gci_part_no' => ['nullable', 'string', 'max:255'],
            'gci_part_name' => ['nullable', 'string', 'max:255'],
            'usage_qty' => ['required_with:gci_part_no', 'nullable', 'numeric', 'min:0.0001'],
        ];
    }
}
