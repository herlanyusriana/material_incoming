<?php

namespace App\Imports;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\GciPart;
use App\Models\Machine;
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
    public int $skippedRows = 0;

    /** @var array<string, true> */
    public array $missingFgParts = [];

    /** @var array<string, true> */
    public array $missingComponentParts = [];

    /** @var array<string, true> */
    public array $missingWipParts = [];

    /** @var array<string, true> */
    public array $missingMachines = [];

    public function __construct(private readonly bool $autoCreateParts = false)
    {
    }

    private function normalizeMakeOrBuy(?string $value): string
    {
        if ($value === null) {
            return 'buy';
        }

        $raw = strtolower(trim($value));
        if (in_array($raw, ['make', 'm'], true)) {
            return 'make';
        }
        if (in_array($raw, ['free_issue', 'free issue', 'freeissue', 'fi'], true)) {
            return 'free_issue';
        }
        if (in_array($raw, ['buy', 'b', 'purchase'], true)) {
            return 'buy';
        }

        return 'buy';
    }

    private function getGciPart(?string $partNo): ?GciPart
    {
        if (!$partNo) {
            return null;
        }
        $partNo = $this->normalizeUpper($partNo) ?? '';
        return GciPart::query()->where('part_no', $partNo)->first();
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

    private function whereNullable($query, string $column, mixed $value)
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return $query->whereNull($column);
        }

        return $query->where($column, $value);
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
            $this->skippedRows++;
            return null;
        }

        $fg = $this->getGciPart($fgPartNo);
        if (!$fg) {
            $this->missingFgParts[$fgPartNo] = true;
            $this->skippedRows++;
            return null;
        }

        // Removed FG classification check as Part model does not support it

        $bom = Bom::firstOrCreate(
            ['part_id' => $fg->id],
            ['status' => 'active']
        );

        $rmPartNo = $this->normalizeUpper($this->firstNonEmpty($row, [
            'rm_part_no',
            'rm_part_number',
            'rm_partno',
            'rm_part_no.',
            'component_part_no',
            'component_part_number',
            'component_part',
            'part_no',
            'part_number'
        ]));

        $wipPartNo = $this->normalizeUpper($this->firstNonEmpty($row, ['wip_part_no', 'wip_part_number', 'wip_partno']));

        $processName = $this->firstNonEmpty($row, ['process_name']);
        $machineNameRaw = $this->firstNonEmpty($row, ['machine_name', 'machine_code', 'machine']);

        // Lookup machine by code or name
        $machineId = null;
        if ($machineNameRaw) {
            $machineNameRaw = trim($machineNameRaw);
            $machine = Machine::where('code', $machineNameRaw)->orWhere('name', $machineNameRaw)->first();
            $machineId = $machine?->id;
            if (!$machine) {
                $this->missingMachines[$machineNameRaw] = true;
            }
        }

        // Check availability of identification data
        $hasPartNo = ($rmPartNo || $wipPartNo);
        $hasProcess = ($processName || $machineId);

        // If neither Part No nor Process is specified, then skip
        if (!$hasPartNo && !$hasProcess) {
            $this->skippedRows++;
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

        $payload = [
            'bom_id' => $bom->id,
            'process_name' => $processName ? trim($processName) : null,
            'machine_id' => $machineId,
            'wip_part_id' => null, // Explicitly null as we use wip_part_no
            'wip_part_no' => $wipPartNo,
            'wip_qty' => $wipQty,
            'wip_uom' => $wipUom,
            'wip_part_name' => $wipPartName ? trim($wipPartName) : null,
            'material_size' => $materialSize ? trim($materialSize) : null,
            'material_spec' => $materialSpec ? trim($materialSpec) : null,
            'material_name' => $materialName ? trim($materialName) : null,
            'special' => $special ? trim($special) : null,
            'component_part_id' => null,
            'component_part_no' => $rmPartNo,
            'make_or_buy' => $makeOrBuy,
            'usage_qty' => $usageQty ?? 1,
            'consumption_uom' => $consumptionUom,
        ];

        if ($wipPartNo) {
            $wip = $this->getGciPart($wipPartNo);
            if (!$wip) {
                $this->missingWipParts[$wipPartNo] = true;
                if ($this->autoCreateParts) {
                    $wip = GciPart::query()->create([
                        'customer_id' => $fg->customer_id,
                        'part_no' => $wipPartNo,
                        'part_name' => $wipPartName ?: ($processName ?: $wipPartNo),
                        'classification' => 'WIP',
                        'status' => 'active',
                    ]);
                }
            }
            if ($wip) {
                $payload['wip_part_id'] = $wip->id;
            }
        }

        if ($rmPartNo) {
            $rm = $this->getGciPart($rmPartNo);
            if (!$rm) {
                $this->missingComponentParts[$rmPartNo] = true;
                if ($this->autoCreateParts) {
                    $rm = GciPart::query()->create([
                        'customer_id' => $fg->customer_id,
                        'part_no' => $rmPartNo,
                        'part_name' => $materialName ?: $rmPartNo,
                        'classification' => 'RM',
                        'status' => 'active',
                    ]);
                }
            }
            if ($rm) {
                $payload['component_part_id'] = $rm->id;
            }
        }

        // Prefer updating by line_no when it is provided.
        // This keeps stable bom_item_id values so existing substitute relations are preserved.
        if ($lineNo !== null) {
            $byLine = BomItem::query()
                ->where('bom_id', $bom->id)
                ->where('line_no', $lineNo)
                ->get();

            if ($byLine->count() === 1) {
                $byLine->first()->update(array_merge($payload, ['line_no' => $lineNo]));
                $this->rowCount++;
                return null;
            }
        }

        // Update only if the same logical row exists; otherwise create a new line.
        // This prevents accidental overwrites when `line_no` is duplicated in the file.
        $signatureQuery = BomItem::query()->where('bom_id', $bom->id);
        $signatureQuery = $this->whereNullable($signatureQuery, 'process_name', $payload['process_name']);
        $signatureQuery = $this->whereNullable($signatureQuery, 'machine_id', $payload['machine_id']);
        $signatureQuery = $this->whereNullable($signatureQuery, 'wip_part_no', $payload['wip_part_no']);
        $signatureQuery = $this->whereNullable($signatureQuery, 'component_part_no', $payload['component_part_no']);
        $signatureQuery = $this->whereNullable($signatureQuery, 'material_size', $payload['material_size']);
        $signatureQuery = $this->whereNullable($signatureQuery, 'material_spec', $payload['material_spec']);
        $signatureQuery = $this->whereNullable($signatureQuery, 'material_name', $payload['material_name']);

        $existing = $signatureQuery->first();
        if ($existing) {
            $existing->update($payload);
            $this->rowCount++;
            return null;
        }

        // Use provided line_no if it's free; otherwise append at the end.
        $requestedLineNo = $lineNo;
        $lineNoTaken = BomItem::query()
            ->where('bom_id', $bom->id)
            ->where('line_no', $requestedLineNo)
            ->exists();

        if ($lineNoTaken) {
            $lineNo = (int) (BomItem::query()->where('bom_id', $bom->id)->max('line_no') ?? 0) + 1;
        }

        $payload['line_no'] = $lineNo;

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
            'machine_name' => ['nullable', 'string', 'max:255'], // kept for import compatibility, resolved to machine_id
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
            'consumption' => ['required_with:rm_part_no', 'nullable', 'numeric', 'min:0'],
            'uom_rm' => ['nullable', 'string', 'max:20'],
            'make_or_buy' => ['nullable', 'in:make,buy,free_issue,MAKE,BUY,FREE_ISSUE,M,B,Purchase,purchase,FI,Free Issue,free issue'],
            // backward compat
            'uom_1' => ['nullable', 'string', 'max:20'],
        ];
    }
}
