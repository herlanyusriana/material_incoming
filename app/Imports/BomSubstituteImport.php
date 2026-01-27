<?php

namespace App\Imports;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\BomItemSubstitute;
use App\Models\GciPart;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;

class BomSubstituteImport implements ToCollection, WithHeadingRow
{
    public int $rowCount = 0;
    protected array $failures = [];
    protected array $seenKeys = [];
    private string $nbsp;

    public function __construct(private readonly bool $autoCreateParts = true)
    {
        $this->nbsp = "\u{00A0}";
    }

    private function normalizePartNo(mixed $value): string
    {
        $normalized = strtoupper(trim((string) $value));
        $normalized = preg_replace('/\\s+/u', '', $normalized) ?? $normalized;

        return $normalized;
    }

    private function normalizedEqualsSql(string $columnSql): string
    {
        // Normalize in SQL to match normalizePartNo(): trim + uppercase + remove regular spaces + remove non-breaking spaces.
        // This is defensive against hidden whitespace in master data (e.g., '4810 JM3004B' vs '4810JM3004B').
        return "REPLACE(REPLACE(UPPER(TRIM({$columnSql})), ' ', ''), ?, '') = ?";
    }

    private function findGciPartByPartNo(string $normalizedPartNo): ?GciPart
    {
        return GciPart::query()
            ->whereRaw($this->normalizedEqualsSql('part_no'), [$this->nbsp, $normalizedPartNo])
            ->first();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowIndex = $index + 2; // Assuming header is row 1

            // normalize keys
            $row = $row->mapWithKeys(fn ($item, $key) => [strtolower(trim((string) $key)) => $item]);

            $validator = Validator::make($row->toArray(), [
                'fg_part_no' => ['required', 'string'],
                'component_part_no' => ['required', 'string'],
                'substitute_part_no' => ['required', 'string'],
                'ratio' => ['nullable', 'numeric', 'min:0.0001'],
                'priority' => ['nullable', 'integer', 'min:1'],
                'status' => ['nullable', 'in:active,inactive'],
                'notes' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $this->failures[] = new ImportFailure($rowIndex, $validator->errors()->all());
                continue;
            }

            $fgPartNo = $this->normalizePartNo($row['fg_part_no']);
            $componentPartNo = $this->normalizePartNo($row['component_part_no']);
            $subPartNo = $this->normalizePartNo($row['substitute_part_no']);
            if ($fgPartNo === '' || $componentPartNo === '' || $subPartNo === '') {
                $this->addFailure($rowIndex, 'fg_part_no/component_part_no/substitute_part_no cannot be empty');
                continue;
            }

            $dedupeKey = "{$fgPartNo}|{$componentPartNo}|{$subPartNo}";
            if (isset($this->seenKeys[$dedupeKey])) {
                $this->addFailure($rowIndex, "Duplicate row in file: {$dedupeKey}");
                continue;
            }
            $this->seenKeys[$dedupeKey] = true;

            // 1. Find FG Part
            $fgPart = $this->findGciPartByPartNo($fgPartNo);

            // 2. Find BOM
            $bom = null;
            if ($fgPart) {
                $bom = Bom::activeVersion($fgPart->id) ?? Bom::query()
                    ->where('part_id', $fgPart->id)
                    ->orderByDesc('effective_date')
                    ->orderByDesc('id')
                    ->first();
            }

            // Fallback: some databases contain duplicate/dirty FG part_no rows; prefer the one that actually has a BOM.
            if (!$bom) {
                $bom = Bom::query()
                    ->join('gci_parts as gp', 'gp.id', '=', 'boms.part_id')
                    ->whereRaw($this->normalizedEqualsSql('gp.part_no'), [$this->nbsp, $fgPartNo])
                    ->orderByDesc('boms.effective_date')
                    ->orderByDesc('boms.id')
                    ->select('boms.*')
                    ->first();
                if ($bom) {
                    $fgPart = $bom->part;
                }
            }

            if (!$fgPart) {
                $this->addFailure($rowIndex, "FG Part not found: $fgPartNo");
                continue;
            }
            if (!$bom) {
                $this->addFailure($rowIndex, "BOM not found for FG: $fgPartNo");
                continue;
            }

            // 3. Find Component/BOM Item
            // Need to match component_part_no against either component_part_no OR componentPart->part_no OR wip_part_no
            $bomItem = BomItem::query()
                ->where('bom_id', $bom->id)
                ->where(function ($q) use ($componentPartNo) {
                    $q->whereRaw($this->normalizedEqualsSql('component_part_no'), [$this->nbsp, $componentPartNo])
                        ->orWhereHas('componentPart', fn ($sq) => $sq->whereRaw($this->normalizedEqualsSql('part_no'), [$this->nbsp, $componentPartNo]))
                        ->orWhereRaw($this->normalizedEqualsSql('wip_part_no'), [$this->nbsp, $componentPartNo]);
                })
                ->first();
            if (!$bomItem) {
                $bomItem = BomItem::query()
                    ->where('bom_id', $bom->id)
                    ->where(function ($q) use ($componentPartNo) {
                        $q->whereRaw("REPLACE(REPLACE(UPPER(TRIM(component_part_no)), ' ', ''), ?, '') LIKE ?", [$this->nbsp, $componentPartNo . '%'])
                            ->orWhereRaw("REPLACE(REPLACE(UPPER(TRIM(wip_part_no)), ' ', ''), ?, '') LIKE ?", [$this->nbsp, $componentPartNo . '%']);
                    })
                    ->first();
            }

            if (!$bomItem) {
                $this->addFailure($rowIndex, "BOM line not found for component: $componentPartNo in BOM $fgPartNo");
                continue;
            }

            // 4. Find Substitute Part
            $subPart = $this->findGciPartByPartNo($subPartNo);
            if (!$subPart && $this->autoCreateParts) {
                $subPart = GciPart::query()->create([
                    'part_no' => $subPartNo,
                    'part_name' => 'AUTO-CREATED (SUBSTITUTE)',
                    'classification' => 'RM',
                    'status' => 'active',
                ]);
            }
            if (!$subPart) {
                $this->addFailure($rowIndex, "Substitute Part not found: $subPartNo");
                continue;
            }

            // 5. Create/Update Substitute (reject duplicates in DB)
            $existing = BomItemSubstitute::query()
                ->where('bom_item_id', (int) $bomItem->id)
                ->where('substitute_part_id', (int) $subPart->id)
                ->get();
            if ($existing->count() > 1) {
                $this->addFailure($rowIndex, "Duplicate substitute records already exist in DB for component {$componentPartNo} / sub {$subPartNo} (FG {$fgPartNo}). Please cleanup duplicates first.");
                continue;
            }

            $payload = [
                'ratio' => $row['ratio'] ?? 1,
                'priority' => $row['priority'] ?? 1,
                'status' => $row['status'] ?? 'active',
                'notes' => $row['notes'] ?? null,
            ];

            if ($existing->count() === 1) {
                $existing->first()->update($payload);
            } else {
                BomItemSubstitute::query()->create(array_merge(
                    [
                        'bom_item_id' => (int) $bomItem->id,
                        'substitute_part_id' => (int) $subPart->id,
                    ],
                    $payload
                ));
            }

            $this->rowCount++;
        }
    }

    protected function addFailure($row, $message)
    {
        $this->failures[] = new ImportFailure((int) $row, [$message]);
    }

    public function failures(): array
    {
        return $this->failures;
    }
}
