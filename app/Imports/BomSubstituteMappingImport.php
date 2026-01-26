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

    public function __construct(private readonly bool $autoCreateParts = true)
    {
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

            $componentPartNo = strtoupper(trim((string) $row['component_part_no']));
            $subPartNo = strtoupper(trim((string) $row['substitute_part_no']));
            if ($componentPartNo === '' || $subPartNo === '') {
                $this->addFailure($rowIndex, 'component_part_no/substitute_part_no cannot be empty');
                continue;
            }

            $supplier = isset($row['supplier']) ? trim((string) $row['supplier']) : '';
            $notes = isset($row['notes']) ? trim((string) $row['notes']) : '';
            $finalNotes = trim(implode(' | ', array_values(array_filter([$supplier !== '' ? $supplier : null, $notes !== '' ? $notes : null]))));

            $subPart = GciPart::query()->where('part_no', $subPartNo)->first();
            if (!$subPart && $this->autoCreateParts) {
                $subPart = GciPart::query()->create([
                    'part_no' => $subPartNo,
                    'part_name' => 'AUTO-CREATED (SUBSTITUTE)',
                    'classification' => 'RM',
                    'status' => 'active',
                ]);
            }

            if (!$subPart) {
                $this->addFailure($rowIndex, "Substitute part not found: {$subPartNo}");
                continue;
            }

            $componentPart = GciPart::query()->where('part_no', $componentPartNo)->first();
            $componentPartId = $componentPart ? (int) $componentPart->id : 0;

            $bomItems = BomItem::query()
                ->when($componentPartId > 0, function ($q) use ($componentPartId) {
                    $q->where('component_part_id', $componentPartId);
                }, function ($q) use ($componentPartNo) {
                    $q->where('component_part_no', $componentPartNo);
                })
                ->get(['id']);

            // Fallback: some BOMs keep only component_part_no even if master exists
            if ($componentPartId > 0) {
                $extra = BomItem::query()
                    ->where('component_part_no', $componentPartNo)
                    ->get(['id']);
                $bomItems = $bomItems->merge($extra)->unique('id')->values();
            }

            if ($bomItems->isEmpty()) {
                $this->addFailure($rowIndex, "No BOM lines found using component: {$componentPartNo}");
                continue;
            }

            foreach ($bomItems as $bomItem) {
                BomItemSubstitute::updateOrCreate(
                    [
                        'bom_item_id' => (int) $bomItem->id,
                        'substitute_part_id' => (int) $subPart->id,
                    ],
                    [
                        'substitute_part_no' => $subPartNo,
                        'ratio' => $row['ratio'] ?? 1,
                        'priority' => $row['priority'] ?? 1,
                        'status' => $row['status'] ?? 'active',
                        'notes' => $finalNotes !== '' ? $finalNotes : null,
                    ]
                );
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
