<?php

namespace App\Imports;

use App\Models\BomItem;
use App\Models\BomItemSubstitute;
use App\Models\GciPart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BomSubstituteMappingImport implements ToCollection, WithHeadingRow
{
    public int $rowCount = 0;
    protected array $failures = [];
    protected array $seenKeys = [];
    /** @var array<string, true> */
    public array $missingComponentParts = [];
    /** @var array<string, true> */
    public array $missingSubstituteParts = [];
    /** @var list<string> */
    private array $stripChars;

    public function __construct(private readonly bool $autoCreateParts = true)
    {
        $this->stripChars = [
            "\u{00A0}", // NBSP
            "\t",
            "\n",
            "\r",
            "\u{200B}", // zero-width space
            "\u{FEFF}", // BOM / zero-width no-break space
        ];
    }

    private function normalizePartNo(mixed $value): string
    {
        $normalized = strtoupper(trim((string) $value));
        $normalized = preg_replace('/\\s+/u', '', $normalized) ?? $normalized;

        return $normalized;
    }

    private function normalizedExprSql(string $columnSql): string
    {
        $expr = "REPLACE(UPPER(TRIM({$columnSql})), ' ', '')";
        foreach ($this->stripChars as $_) {
            $expr = "REPLACE({$expr}, ?, '')";
        }
        return $expr;
    }

    private function findGciPartByPartNo(string $normalizedPartNo): ?GciPart
    {
        return GciPart::query()
            ->whereRaw($this->normalizedExprSql('part_no') . ' = ?', array_merge($this->stripChars, [$normalizedPartNo]))
            ->first();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowIndex = $index + 2; // header row = 1

            $row = $row->mapWithKeys(fn ($item, $key) => [strtolower(trim((string) $key)) => $item]);

            $validator = Validator::make($row->toArray(), [
                'component_part_no' => ['required', 'string'],
                'substitute_part_no' => ['required', 'string'],
                'supplier' => ['nullable', 'string', 'max:255'],
                'ratio' => ['nullable', 'numeric', 'min:0.0001'],
                'priority' => ['nullable', 'integer', 'min:1'],
                'status' => ['nullable', 'in:active,inactive'],
                'notes' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $this->addFailure($rowIndex, implode(' | ', $validator->errors()->all()));
                continue;
            }

            $componentPartNo = $this->normalizePartNo($row['component_part_no']);
            $subPartNo = $this->normalizePartNo($row['substitute_part_no']);
            if ($componentPartNo === '' || $subPartNo === '') {
                $this->addFailure($rowIndex, 'component_part_no/substitute_part_no cannot be empty');
                continue;
            }

            $dedupeKey = "{$componentPartNo}|{$subPartNo}";
            if (isset($this->seenKeys[$dedupeKey])) {
                $this->addFailure($rowIndex, "Duplicate row in file: {$dedupeKey}");
                continue;
            }
            $this->seenKeys[$dedupeKey] = true;

            $supplier = isset($row['supplier']) ? trim((string) $row['supplier']) : '';
            $notes = isset($row['notes']) ? trim((string) $row['notes']) : '';
            $finalNotes = trim(implode(' | ', array_values(array_filter([$supplier !== '' ? $supplier : null, $notes !== '' ? $notes : null]))));

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
                $this->missingSubstituteParts[$subPartNo] = true;
                $this->addFailure($rowIndex, "Substitute part not found: {$subPartNo}");
                continue;
            }

            $componentPart = $this->findGciPartByPartNo($componentPartNo);
            $componentPartId = $componentPart ? (int) $componentPart->id : 0;
            if ($componentPartId <= 0) {
                $this->missingComponentParts[$componentPartNo] = true;
            }

            $bomItems = BomItem::query()
                ->when($componentPartId > 0, function ($q) use ($componentPartId) {
                    $q->where('component_part_id', $componentPartId);
                }, function ($q) use ($componentPartNo) {
                    $q->whereRaw($this->normalizedExprSql('component_part_no') . ' = ?', array_merge($this->stripChars, [$componentPartNo]));
                })
                ->get(['id']);

            // Fallback: some BOMs keep only component_part_no even if master exists
            if ($componentPartId > 0) {
                $extra = BomItem::query()
                    ->whereRaw($this->normalizedExprSql('component_part_no') . ' = ?', array_merge($this->stripChars, [$componentPartNo]))
                    ->get(['id']);
                $bomItems = $bomItems->merge($extra)->unique('id')->values();
            }

            if ($bomItems->isEmpty()) {
                $this->addFailure($rowIndex, "No BOM lines found using component: {$componentPartNo}");
                continue;
            }

            foreach ($bomItems as $bomItem) {
                $existing = BomItemSubstitute::query()
                    ->where('bom_item_id', (int) $bomItem->id)
                    ->where('substitute_part_id', (int) $subPart->id)
                    ->get();
                if ($existing->count() > 1) {
                    $this->addFailure($rowIndex, "Duplicate substitute records already exist in DB for component {$componentPartNo} / sub {$subPartNo}. Please cleanup duplicates first.");
                    continue;
                }

                $payload = [
                    'substitute_part_no' => $subPartNo,
                    'ratio' => $row['ratio'] ?? 1,
                    'priority' => $row['priority'] ?? 1,
                    'status' => $row['status'] ?? 'active',
                    'notes' => $finalNotes !== '' ? $finalNotes : null,
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
            }

            $this->rowCount++;
        }
    }

    protected function addFailure(int $row, string $message): void
    {
        $this->failures[] = new ImportFailure($row, [$message]);
    }

    public function failures(): array
    {
        return $this->failures;
    }
}
