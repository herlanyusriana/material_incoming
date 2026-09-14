<?php

namespace App\Imports;

use App\Models\MaterialSubstitute;
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
    /** @var array<string, true> */
    public array $missingGenericParts = [];
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
            $rowIndex = $index + 2; // Assuming header is row 1

            // normalize keys
            $row = $row->mapWithKeys(fn($item, $key) => [strtolower(trim((string) $key)) => $item]);

            $validator = Validator::make($row->toArray(), [
                'generic_part_no' => ['required', 'string'],
                'generic_part_name' => ['nullable', 'string', 'max:255'],
                'substitute_part_no' => ['required', 'string'],
                'substitute_part_name' => ['nullable', 'string', 'max:255'],
                'ratio' => ['nullable', 'numeric', 'min:0.0001'],
                'priority' => ['nullable', 'integer', 'min:1'],
                'status' => ['nullable', 'in:active,inactive'],
                'notes' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $this->failures[] = new ImportFailure($rowIndex, $validator->errors()->all());
                continue;
            }

            $genericPartNo = $this->normalizePartNo($row['generic_part_no']);
            $subPartNo = $this->normalizePartNo($row['substitute_part_no']);
            if ($genericPartNo === '' || $subPartNo === '') {
                $this->addFailure($rowIndex, 'generic_part_no/substitute_part_no cannot be empty');
                continue;
            }

            $dedupeKey = "{$genericPartNo}|{$subPartNo}";
            if (isset($this->seenKeys[$dedupeKey])) {
                $this->addFailure($rowIndex, "Duplicate row in file: {$dedupeKey}");
                continue;
            }
            $this->seenKeys[$dedupeKey] = true;

            // 1. Find Generic (RM/WIP) Part
            $genericPart = $this->findGciPartByPartNo($genericPartNo);
            if (!$genericPart) {
                $this->missingGenericParts[$genericPartNo] = true;
                $this->addFailure($rowIndex, "Generic part not found: $genericPartNo");
                continue;
            }
            if (!empty($row['generic_part_name'])) {
                $name = trim((string) $row['generic_part_name']);
                if ($name !== '' && $genericPart->part_name !== $name) {
                    $genericPart->update(['part_name' => $name]);
                }
            }

            // 2. Find Substitute Part
            $subPart = $this->findGciPartByPartNo($subPartNo);
            if ($this->autoCreateParts && !$subPart) {
                $subPart = GciPart::query()->create([
                    'part_no' => $subPartNo,
                    'part_name' => !empty($row['substitute_part_name']) ? $row['substitute_part_name'] : $subPartNo,
                    'classification' => 'RM',
                    'status' => 'active',
                ]);
            }

            if (!$subPart) {
                $this->missingSubstituteParts[$subPartNo] = true;
                $this->addFailure($rowIndex, "Substitute Part not found: $subPartNo");
                continue;
            }

            // 3. Create global substitute (skip if exists)
            $existing = MaterialSubstitute::query()
                ->where('generic_part_id', (int) $genericPart->id)
                ->where('substitute_part_id', (int) $subPart->id)
                ->exists();

            if ($existing) {
                continue; // Skip if already exists to protect data integrity
            }

            MaterialSubstitute::query()->create([
                'generic_part_id' => (int) $genericPart->id,
                'substitute_part_id' => (int) $subPart->id,
                'ratio' => $row['ratio'] ?? 1,
                'priority' => $row['priority'] ?? 1,
                'status' => $row['status'] ?? 'active',
                'notes' => isset($row['notes']) ? trim((string) $row['notes']) : null,
            ]);

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