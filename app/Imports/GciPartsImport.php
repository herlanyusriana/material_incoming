<?php

namespace App\Imports;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\BomItemSubstitute;
use App\Models\GciPart;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class GciPartsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnFailure
{
    use SkipsFailures;

    public int $createdCount = 0;
    public int $updatedCount = 0;
    public int $substituteCount = 0;

    /** @var array<string, true> */
    public array $missingComponentParts = [];

    /** @var array<string, true> */
    public array $missingSubstituteParts = [];

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

    public function collection(\Illuminate\Support\Collection $rows)
    {
        foreach ($rows as $row) {
            $row = $row instanceof \Illuminate\Support\Collection ? $row->toArray() : (array) $row;
            $this->processRow($row);
        }
    }

    private function processRow(array $row)
    {
        $partNo = $this->norm($row['part_no'] ?? $row['part_number'] ?? null);
        $partNo = $partNo !== null ? strtoupper($partNo) : null;
        if (!$partNo) {
            return;
        }

        $partName = $this->norm($row['part_name'] ?? $row['part_name_gci'] ?? null);
        $model = $this->norm($row['model'] ?? null);
        $status = strtolower((string) ($this->norm($row['status'] ?? null) ?? ''));
        $customerCode = $this->norm($row['customer'] ?? null);

        // Resolve customer
        $customer = null;
        if ($customerCode !== null) {
            $customer = \App\Models\Customer::where('name', $customerCode)->first();
        }

        // Find existing part by part_no + customer
        $query = GciPart::where('part_no', $partNo);
        if ($customer) {
            $query->where('customer_id', $customer->id);
        } else {
            $query->whereNull('customer_id');
        }
        $existingPart = $query->first();

        $classification = strtoupper((string) ($this->norm($row['classification'] ?? null) ?? ''));
        if (!in_array($classification, ['FG', 'WIP', 'RM'], true)) {
            $classification = 'FG';
        }

        if ($existingPart) {
            // UPDATE existing part
            $updates = [];
            if ($partName !== null) {
                $updates['part_name'] = $partName;
            }
            if ($model !== null) {
                $updates['model'] = $model;
            }
            if ($status !== '' && in_array($status, ['active', 'inactive'], true)) {
                $updates['status'] = $status;
            }
            $updates['classification'] = $classification;
            if ($customer) {
                $updates['customer_id'] = $customer->id;
            }

            $existingPart->update($updates);
            $this->updatedCount++;

            $part = $existingPart;
        } else {
            // CREATE new part
            $part = new GciPart();
            $part->part_no = $partNo;
            $part->classification = $classification;
            $part->part_name = $partName ?? $partNo;
            $part->model = $model;
            $part->status = ($status !== '' && in_array($status, ['active', 'inactive'], true)) ? $status : 'active';
            $part->customer_id = $customer?->id;
            $part->save();
            $this->createdCount++;
        }

        // Process substitute if provided
        $this->processSubstitute($row, $part);
    }

    private function processSubstitute(array $row, GciPart $fgPart): void
    {
        $componentPartNo = $this->norm($row['component_part_no'] ?? null);
        $substitutePartNo = $this->norm($row['substitute_part_no'] ?? null);

        if (!$componentPartNo || !$substitutePartNo) {
            return;
        }

        $componentPartNo = strtoupper($componentPartNo);
        $substitutePartNo = strtoupper($substitutePartNo);

        // Find the BOM for this FG part
        $bom = Bom::where('part_id', $fgPart->id)->latest()->first();
        if (!$bom) {
            return;
        }

        // Find the component GCI part
        $componentPart = GciPart::where('part_no', $componentPartNo)->first();
        if (!$componentPart) {
            $this->missingComponentParts[$componentPartNo] = true;
            return;
        }

        // Find the BomItem for this component
        $bomItem = BomItem::where('bom_id', $bom->id)
            ->where('component_part_id', $componentPart->id)
            ->first();
        if (!$bomItem) {
            return;
        }

        // Find the substitute GCI part
        $substitutePart = GciPart::where('part_no', $substitutePartNo)->first();
        if (!$substitutePart) {
            $this->missingSubstituteParts[$substitutePartNo] = true;
            return;
        }

        // Upsert substitute
        $ratio = $this->norm($row['substitute_ratio'] ?? $row['ratio'] ?? null);
        $priority = $this->norm($row['substitute_priority'] ?? $row['priority'] ?? null);
        $subStatus = $this->norm($row['substitute_status'] ?? null);
        $notes = $this->norm($row['substitute_notes'] ?? $row['notes'] ?? null);

        BomItemSubstitute::updateOrCreate(
            [
                'bom_item_id' => $bomItem->id,
                'substitute_part_id' => $substitutePart->id,
            ],
            [
                'substitute_part_no' => $substitutePartNo,
                'ratio' => $ratio !== null && is_numeric($ratio) ? (float) $ratio : 1,
                'priority' => $priority !== null && is_numeric($priority) ? (int) $priority : 1,
                'status' => ($subStatus && in_array(strtolower($subStatus), ['active', 'inactive'], true)) ? strtolower($subStatus) : 'active',
                'notes' => $notes,
            ]
        );

        $this->substituteCount++;
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
            'component_part_no' => ['nullable', 'string', 'max:100'],
            'substitute_part_no' => ['nullable', 'string', 'max:100'],
            'substitute_ratio' => ['nullable', 'numeric', 'min:0.0001'],
            'ratio' => ['nullable', 'numeric', 'min:0.0001'],
            'substitute_priority' => ['nullable', 'integer', 'min:1'],
            'priority' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
