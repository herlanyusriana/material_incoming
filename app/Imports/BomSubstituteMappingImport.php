<?php

namespace App\Imports;

use App\Models\MaterialSubstitute;
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
            $rowIndex = $index + 2; // header row = 1

            $row = $row->mapWithKeys(fn($item, $key) => [strtolower(trim((string) $key)) => $item]);

            $validator = Validator::make($row->toArray(), [
                'generic_part_no' => ['required', 'string'],
                'generic_part_name' => ['nullable', 'string', 'max:255'],
                'substitute_part_no' => ['required', 'string'],
                'substitute_part_name' => ['nullable', 'string', 'max:255'],
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

            $supplier = isset($row['supplier']) ? trim((string) $row['supplier']) : '';
            $notes = isset($row['notes']) ? trim((string) $row['notes']) : '';
            $finalNotes = trim(implode(' | ', array_values(array_filter([$supplier !== '' ? $supplier : null, $notes !== '' ? $notes : null]))));

            $subPart = $this->findGciPartByPartNo($subPartNo);
            if ($this->autoCreateParts && !$subPart) {
                $subPart = GciPart::query()->create([
                    'part_no' => $subPartNo,
                    'part_name' => !empty($row['substitute_part_name']) ? $row['substitute_part_name'] : $subPartNo,
                    'classification' => 'RM',
                    'status' => 'active',
                ]);
            }
            // If substitute exists, always allow updating its name from import
            if ($subPart && !empty($row['substitute_part_name'])) {
                $subName = trim((string) $row['substitute_part_name']);
                if ($subName !== '' && $subPart->part_name !== $subName) {
                    $subPart->update(['part_name' => $subName]);
                }
            }

            if (!$subPart) {
                $this->missingSubstituteParts[$subPartNo] = true;
                $this->addFailure($rowIndex, "Substitute part not found: {$subPartNo}");
                continue;
            }

            $genericPart = $this->findGciPartByPartNo($genericPartNo);
            if (!$genericPart) {
                $this->missingGenericParts[$genericPartNo] = true;
                $this->addFailure($rowIndex, "Generic part not found: {$genericPartNo}");
                continue;
            }
            if (!empty($row['generic_part_name'])) {
                $genName = trim((string) $row['generic_part_name']);
                if ($genName !== '' && $genericPart->part_name !== $genName) {
                    $genericPart->update(['part_name' => $genName]);
                }
            }

            $payload = [
                'ratio' => $row['ratio'] ?? 1,
                'priority' => $row['priority'] ?? 1,
                'status' => $row['status'] ?? 'active',
                'notes' => $finalNotes !== '' ? $finalNotes : null,
            ];

            MaterialSubstitute::updateOrCreate(
                [
                    'generic_part_id' => (int) $genericPart->id,
                    'substitute_part_id' => (int) $subPart->id,
                ],
                $payload
            );

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