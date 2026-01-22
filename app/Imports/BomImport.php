<?php

namespace App\Imports;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Part;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class BomImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use SkipsFailures;

    public int $rowCount = 0;

    private function normalizeMakeOrBuy(?string $value): string
    {
        if ($value === null) {
            return 'buy';
        }

        $raw = strtolower(trim($value));
        if (in_array($raw, ['make', 'm'], true)) {
            return 'make';
        }
        if (in_array($raw, ['buy', 'b', 'purchase'], true)) {
            return 'buy';
        }

        return 'buy';
    }

    private function getPart(string $partNo): ?Part
    {
        $partNo = $this->normalizeUpper($partNo) ?? '';
        return Part::query()->where('part_no', $partNo)->first();
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
        $value = trim($value);
        return $value === '' ? null : strtoupper($value);
    }

    public function prepareForValidation(array $data, int $index): array
    {
        $upperKeys = [
            'fg_part_no',
            'wip_part_no',
            'rm_part_no',
            'uom_wip',
            'uom_rm',
            // backward compat
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

        $fg = $this->getPart($fgPartNo);
        if (!$fg) {
            throw new \Exception("FG Part No [{$fgPartNo}] belum terdaftar di Parts.");
        }

        // Removed FG classification check as Part model does not support it

        $bom = Bom::firstOrCreate(
            ['part_id' => $fg->id],
            ['status' => 'active']
        );

        $rmPartNo = $this->normalizeUpper($this->firstNonEmpty($row, ['rm_part_no', 'rm_part_number', 'rm_partno']));
        if (!$rmPartNo) {
            return null;
        }

        $materialNameForRm = $this->firstNonEmpty($row, ['material_name']);
        $makeOrBuyRaw = $this->firstNonEmpty($row, ['make_or_buy', 'makebuy', 'make_buy']);
        $makeOrBuy = $this->normalizeMakeOrBuy($makeOrBuyRaw);

        $lineNoRaw = $this->firstNonEmpty($row, ['no', 'line_no', 'line']);
        $lineNo = null;
        if ($lineNoRaw !== null && is_numeric($lineNoRaw)) {
            $lineNo = (int) $lineNoRaw;
        }

        $processName = $this->firstNonEmpty($row, ['process_name']);
        $machineName = $this->firstNonEmpty($row, ['machine_name']);

        $wipPartNo = $this->normalizeUpper($this->firstNonEmpty($row, ['wip_part_no', 'wip_part_number', 'wip_partno']));
        
        $wipQtyRaw = $this->firstNonEmpty($row, ['qty_wip', 'qty', 'qty_']);
        $wipQty = $wipQtyRaw !== null && is_numeric($wipQtyRaw) ? (float) $wipQtyRaw : null;

        $wipUom = $this->normalizeUpper($this->firstNonEmpty($row, ['uom_wip', 'uom']));
        $wipPartName = $this->firstNonEmpty($row, ['wip_part_name']);

        $materialSize = $this->firstNonEmpty($row, ['material_size']);
        $materialSpec = $this->firstNonEmpty($row, ['material_spec']);
        $materialName = $this->firstNonEmpty($row, ['material_name']);
        $special = $this->firstNonEmpty($row, ['spesial', 'special']);

        $usageQtyRaw = $this->firstNonEmpty($row, ['consumption', 'usage_qty', 'usage']);
        $usageQty = $usageQtyRaw !== null && is_numeric($usageQtyRaw) ? (float) $usageQtyRaw : null;

        $consumptionUom = $this->normalizeUpper($this->firstNonEmpty($row, ['uom_rm', 'uom_1', 'uom_2', 'consumption_uom']));

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
            'wip_part_id' => null, // Explicitly null as we use wip_part_no
            'wip_part_no' => $wipPartNo,
            'wip_qty' => $wipQty,
            'wip_uom' => $wipUom,
            'wip_part_name' => $wipPartName ? trim($wipPartName) : null,
            'material_size' => $materialSize ? trim($materialSize) : null,
            'material_spec' => $materialSpec ? trim($materialSpec) : null,
            'material_name' => $materialName ? trim($materialName) : null,
            'special' => $special ? trim($special) : null,
            'component_part_id' => null, // RM/WIP components are now strings only
            'component_part_no' => $rmPartNo,
            'make_or_buy' => $makeOrBuy,
            'usage_qty' => $usageQty ?? 1,
            'consumption_uom' => $consumptionUom,
        ];

        if ($item) {
            $item->update($payload);
            $this->rowCount++;
            return null;
        }

        BomItem::create($payload);
        $this->rowCount++;
        return null;
    }

    public function rules(): array
    {
        return [
            'no' => ['nullable', 'integer', 'min:1'],
            'fg_name' => ['nullable', 'string', 'max:255'],
            'fg_model' => ['nullable', 'string', 'max:255'],
            'fg_part_no' => ['required', 'string', 'max:255'],
            'process_name' => ['nullable', 'string', 'max:255'],
            'machine_name' => ['nullable', 'string', 'max:255'],
            'wip_part_no' => ['nullable', 'string', 'max:255'],
            'qty_wip' => ['nullable', 'numeric', 'min:0'],
            'uom_wip' => ['nullable', 'string', 'max:20'],
            // backward compat
            'qty' => ['nullable', 'numeric', 'min:0'],
            'uom' => ['nullable', 'string', 'max:20'],
            'wip_part_name' => ['nullable', 'string', 'max:255'],
            'material_size' => ['nullable', 'string', 'max:255'],
            'material_spec' => ['nullable', 'string', 'max:255'],
            'material_name' => ['nullable', 'string', 'max:255'],
            'spesial' => ['nullable', 'string', 'max:255'],
            'special' => ['nullable', 'string', 'max:255'],
            'rm_part_no' => ['nullable', 'string', 'max:255'],
            'consumption' => ['required_with:rm_part_no', 'nullable', 'numeric', 'min:0.0001'],
            'uom_rm' => ['nullable', 'string', 'max:20'],
            'make_or_buy' => ['nullable', 'in:make,buy,MAKE,BUY,M,B,Purchase,purchase'],
            // backward compat
            'uom_1' => ['nullable', 'string', 'max:20'],
        ];
    }
}
