<?php

namespace App\Imports;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\GciPart;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class BomImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
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
        $value = trim($value);
        return $value === '' ? null : strtoupper($value);
    }

    public function prepareForValidation(array $data, int $index): array
    {
        $upperKeys = [
            'fg_part_no',
            'wip_part_no',
            'rm_part_no',
            'uom',
            'uom_1',
            'uom_2',
        ];

        foreach ($upperKeys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $data[$key] = $this->normalizeUpper(is_string($data[$key] ?? null) ? $data[$key] : (string) $data[$key]);
        }

        return $data;
    }

    public function model(array $row)
    {
        $fgPartNo = $this->firstNonEmpty($row, ['fg_part_no', 'fg_part_number', 'fg_partno']);
        $fgPartNo = $this->normalizeUpper($fgPartNo);
        if (!$fgPartNo) {
            return null;
        }

        $fg = GciPart::query()->where('part_no', $fgPartNo)->first();
        if (!$fg) {
            return null;
        }

        $fgName = $this->firstNonEmpty($row, ['fg_name']);
        $fgModel = $this->firstNonEmpty($row, ['fg_model']);
        if ($fgName !== null || $fgModel !== null) {
            $updates = [];
            if ($fgName !== null && trim($fgName) !== '') {
                $updates['part_name'] = trim($fgName);
            }
            if ($fgModel !== null && trim($fgModel) !== '') {
                $updates['model'] = trim($fgModel);
            }
            if (!empty($updates)) {
                $fg->fill($updates);
                if ($fg->isDirty()) {
                    $fg->save();
                }
            }
        }

        $bom = Bom::firstOrCreate(
            ['part_id' => $fg->id],
            ['status' => 'active']
        );

        $rmPartNo = $this->normalizeUpper($this->firstNonEmpty($row, ['rm_part_no', 'rm_part_number', 'rm_partno']));
        if (!$rmPartNo) {
            return null;
        }

        $component = GciPart::query()->where('part_no', $rmPartNo)->first();
        if (!$component) {
            return null;
        }

        $lineNoRaw = $this->firstNonEmpty($row, ['no', 'line_no', 'line']);
        $lineNo = null;
        if ($lineNoRaw !== null && is_numeric($lineNoRaw)) {
            $lineNo = (int) $lineNoRaw;
        }

        $processName = $this->firstNonEmpty($row, ['process_name']);
        $machineName = $this->firstNonEmpty($row, ['machine_name']);

        $wipPartNo = $this->normalizeUpper($this->firstNonEmpty($row, ['wip_part_no', 'wip_part_number', 'wip_partno']));
        $wipPart = null;
        if ($wipPartNo) {
            $wipPart = GciPart::query()->where('part_no', $wipPartNo)->first();
        }

        $wipQtyRaw = $this->firstNonEmpty($row, ['qty', 'qty_']);
        $wipQty = $wipQtyRaw !== null && is_numeric($wipQtyRaw) ? (float) $wipQtyRaw : null;

        $wipUom = $this->normalizeUpper($this->firstNonEmpty($row, ['uom']));
        $wipPartName = $this->firstNonEmpty($row, ['wip_part_name']);

        $materialSize = $this->firstNonEmpty($row, ['material_size']);
        $materialSpec = $this->firstNonEmpty($row, ['material_spec']);
        $materialName = $this->firstNonEmpty($row, ['material_name']);
        $special = $this->firstNonEmpty($row, ['spesial', 'special']);

        $usageQtyRaw = $this->firstNonEmpty($row, ['consumption', 'usage_qty', 'usage']);
        $usageQty = $usageQtyRaw !== null && is_numeric($usageQtyRaw) ? (float) $usageQtyRaw : null;

        $consumptionUom = $this->normalizeUpper($this->firstNonEmpty($row, ['uom_1', 'uom_2', 'consumption_uom']));

        if ($lineNo === null) {
            $next = (int) (BomItem::query()->where('bom_id', $bom->id)->max('line_no') ?? 0) + 1;
            $lineNo = $next > 0 ? $next : 1;
        }

        $item = BomItem::query()
            ->where('bom_id', $bom->id)
            ->where('line_no', $lineNo)
            ->first();

        $payload = [
            'bom_id' => $bom->id,
            'line_no' => $lineNo,
            'process_name' => $processName ? trim($processName) : null,
            'machine_name' => $machineName ? trim($machineName) : null,
            'wip_part_id' => $wipPart?->id,
            'wip_qty' => $wipQty,
            'wip_uom' => $wipUom,
            'wip_part_name' => $wipPartName ? trim($wipPartName) : null,
            'material_size' => $materialSize ? trim($materialSize) : null,
            'material_spec' => $materialSpec ? trim($materialSpec) : null,
            'material_name' => $materialName ? trim($materialName) : null,
            'special' => $special ? trim($special) : null,
            'component_part_id' => $component->id,
            'usage_qty' => $usageQty ?? 1,
            'consumption_uom' => $consumptionUom,
        ];

        if ($item) {
            $item->update($payload);
            return null;
        }

        BomItem::create($payload);
        return null;
    }

    public function rules(): array
    {
        return [
            'no' => ['nullable', 'integer', 'min:1'],
            'fg_name' => ['nullable', 'string', 'max:255'],
            'fg_model' => ['nullable', 'string', 'max:255'],
            'fg_part_no' => ['required', 'string', 'max:255', Rule::exists('gci_parts', 'part_no')],
            'process_name' => ['nullable', 'string', 'max:255'],
            'machine_name' => ['nullable', 'string', 'max:255'],
            'wip_part_no' => ['nullable', 'string', 'max:255', Rule::exists('gci_parts', 'part_no')],
            'qty' => ['nullable', 'numeric', 'min:0'],
            'uom' => ['nullable', 'string', 'max:20'],
            'wip_part_name' => ['nullable', 'string', 'max:255'],
            'material_size' => ['nullable', 'string', 'max:255'],
            'material_spec' => ['nullable', 'string', 'max:255'],
            'material_name' => ['nullable', 'string', 'max:255'],
            'spesial' => ['nullable', 'string', 'max:255'],
            'special' => ['nullable', 'string', 'max:255'],
            'rm_part_no' => ['nullable', 'string', 'max:255', Rule::exists('gci_parts', 'part_no')],
            'consumption' => ['required_with:rm_part_no', 'nullable', 'numeric', 'min:0.0001'],
            'uom_1' => ['nullable', 'string', 'max:20'],
        ];
    }
}
