<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerPart;
use App\Models\CustomerPartComponent;
use App\Models\GciPart;
use Illuminate\Support\Facades\Schema;
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

    public $duplicates = [];
    private array $seenMappingKeys = [];
    private ?bool $hasLegacyPartIdColumn = null;
    private ?bool $hasGciPartIdColumn = null;

    private function hasLegacyPartIdColumn(): bool
    {
        if ($this->hasLegacyPartIdColumn !== null) {
            return $this->hasLegacyPartIdColumn;
        }

        $this->hasLegacyPartIdColumn = Schema::hasTable('customer_part_components')
            && Schema::hasColumn('customer_part_components', 'part_id');

        return $this->hasLegacyPartIdColumn;
    }

    private function hasGciPartIdColumn(): bool
    {
        if ($this->hasGciPartIdColumn !== null) {
            return $this->hasGciPartIdColumn;
        }

        $this->hasGciPartIdColumn = Schema::hasTable('customer_part_components')
            && Schema::hasColumn('customer_part_components', 'gci_part_id');

        return $this->hasGciPartIdColumn;
    }

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

    private function ensureGciPart(int $customerId, string $partNo, ?string $partName = null): GciPart
    {
        $partNo = $this->normalizeUpper($partNo) ?? '';
        $partName = $partName !== null ? trim($partName) : null;

        $existing = GciPart::query()
            ->where('customer_id', $customerId)
            ->where('part_no', $partNo)
            ->first();
        if ($existing) {
            if ($partName !== null && $partName !== '' && ($existing->part_name === null || trim((string) $existing->part_name) === '')) {
                $existing->update(['part_name' => $partName]);
            }
            return $existing;
        }

        return GciPart::create([
            'customer_id' => $customerId,
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

        // Check if CustomerPart exists
        $customerPart = CustomerPart::where('customer_id', $customer->id)
            ->where('customer_part_no', $customerPartNo)
            ->first();

        $line = $this->firstNonEmpty($row, ['line', 'prod_line', 'production_line']);
        $caseName = $this->firstNonEmpty($row, ['case', 'case_name', 'packing_case', 'box', 'case_type']);

        if (!$customerPart) {
            // Create New
            $customerPart = CustomerPart::create([
                'customer_id' => $customer->id,
                'customer_part_no' => $customerPartNo,
                'customer_part_name' => $customerPartName !== null && trim($customerPartName) !== '' ? trim($customerPartName) : null,
                'line' => $line,
                'case_name' => $caseName,
                'status' => $status,
            ]);
        } else {
            // If exists, update line and case info if available.
            if ($line !== null || $caseName !== null) {
                $updateStart = [];
                if ($line !== null)
                    $updateStart['line'] = $line;
                if ($caseName !== null)
                    $updateStart['case_name'] = $caseName;
                $customerPart->update($updateStart);
            }
        }
        // If exists, we proceed (to check mapping), but we do NOT update the CustomerPart details.

        $gciPartNo = $this->normalizeUpper($this->firstNonEmpty($row, ['gci_part_no', 'part_gci', 'part_no', 'gci_part_number']));

        // If no GCI part specified, we are done
        if (!$gciPartNo) {
            if ($customerPart->wasRecentlyCreated) {
                return null;
            } else {
                // Even if header exists, we might need to process components in next rows if this file structure is flat.
                // But if part info is missing, nothing to do.
                return null;
            }
        }

        $mappingKey = "{$customerCode}|{$customerPartNo}|{$gciPartNo}";
        if (isset($this->seenMappingKeys[$mappingKey])) {
            $this->duplicates[] = [
                'customer_code' => $customerCode,
                'customer_part_no' => $customerPartNo,
                'gci_part_no' => $gciPartNo,
            ];
        }
        $this->seenMappingKeys[$mappingKey] = true;

        $gciPartName = $this->firstNonEmpty($row, ['gci_part_name', 'part_name']);
        $gciPart = $this->ensureGciPart((int) $customer->id, $gciPartNo, $gciPartName);

        $usageQtyRaw = $this->firstNonEmpty($row, ['usage_qty', 'usage', 'consumption']);
        $usageQty = $usageQtyRaw !== null && is_numeric($usageQtyRaw) ? (float) $usageQtyRaw : null;
        if ($usageQty === null) {
            return null;
        }

        $mappingQuery = CustomerPartComponent::query()->where('customer_part_id', $customerPart->id);
        $hasGciPartId = $this->hasGciPartIdColumn();
        $hasLegacyPartId = $this->hasLegacyPartIdColumn();

        if ($hasGciPartId && $hasLegacyPartId) {
            $mappingQuery->where(function ($q) use ($gciPart) {
                $q->where('gci_part_id', $gciPart->id)->orWhere('part_id', $gciPart->id);
            });
        } elseif ($hasGciPartId) {
            $mappingQuery->where('gci_part_id', $gciPart->id);
        } elseif ($hasLegacyPartId) {
            $mappingQuery->where('part_id', $gciPart->id);
        } else {
            // No usable key column in legacy schema; nothing we can do safely.
            return null;
        }

        $mapping = $mappingQuery->first();

        if ($mapping) {
            // Sum quantity (support repeated rows for the same mapping)
            $current = is_numeric($mapping->qty_per_unit) ? (float) $mapping->qty_per_unit : 0.0;
            $updatePayload = ['qty_per_unit' => $current + $usageQty];
            if ($hasGciPartId) {
                $updatePayload['gci_part_id'] = $gciPart->id;
            }
            if ($hasLegacyPartId) {
                $updatePayload['part_id'] = $gciPart->id;
            }
            $mapping->update($updatePayload);
        } else {
            $createPayload = [
                'customer_part_id' => $customerPart->id,
                'qty_per_unit' => $usageQty,
            ];
            if ($hasGciPartId) {
                $createPayload['gci_part_id'] = $gciPart->id;
            }
            if ($hasLegacyPartId) {
                $createPayload['part_id'] = $gciPart->id;
            }

            CustomerPartComponent::create($createPayload);
        }

        return null;
    }

    public function rules(): array
    {
        return [
            'customer_code' => ['required', 'string', 'max:50', Rule::exists('customers', 'code')],
            'customer_part_no' => ['required', 'string', 'max:100'],
            'customer_part_name' => ['nullable', 'string', 'max:255'],
            'line' => ['nullable', 'string', 'max:255'],
            'case' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'gci_part_no' => ['nullable', 'string', 'max:255'],
            'gci_part_name' => ['nullable', 'string', 'max:255'],
            'usage_qty' => ['required_with:gci_part_no', 'nullable', 'numeric', 'min:0.0001'],
        ];
    }
}
