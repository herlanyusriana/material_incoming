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
use Illuminate\Validation\Rule;

class BomSubstituteImport implements ToCollection, WithHeadingRow
{
    public int $rowCount = 0;
    protected $failures = [];

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
                $this->failures[] = (object) [
                    'row' => fn() => $rowIndex,
                    'errors' => fn() => $validator->errors()->all(),
                ];
                continue;
            }

            $fgPartNo = trim((string) $row['fg_part_no']);
            $componentPartNo = trim((string) $row['component_part_no']);
            $subPartNo = trim((string) $row['substitute_part_no']);

            // 1. Find FG Part
            $fgPart = GciPart::where('part_no', $fgPartNo)->first();
            if (!$fgPart) {
                $this->addFailure($rowIndex, "FG Part not found: $fgPartNo");
                continue;
            }

            // 2. Find BOM
            $bom = Bom::where('part_id', $fgPart->id)->first();
            if (!$bom) {
                $this->addFailure($rowIndex, "BOM not found for FG: $fgPartNo");
                continue;
            }

            // 3. Find Component/BOM Item
            // Need to match component_part_no against either component_part_no OR componentPart->part_no OR wip_part_no
            $bomItem = BomItem::query()
                ->where('bom_id', $bom->id)
                ->where(function ($q) use ($componentPartNo) {
                    $q->where('component_part_no', $componentPartNo)
                      ->orWhereHas('componentPart', fn($sq) => $sq->where('part_no', $componentPartNo))
                      ->orWhere('wip_part_no', $componentPartNo);
                })
                ->first();

            if (!$bomItem) {
                $this->addFailure($rowIndex, "BOM line not found for component: $componentPartNo in BOM $fgPartNo");
                continue;
            }

            // 4. Find Substitute Part
            $subPart = GciPart::where('part_no', $subPartNo)->first();
            if (!$subPart) {
                $this->addFailure($rowIndex, "Substitute Part not found: $subPartNo");
                continue;
            }

            // 5. Create/Update Substitute
            BomItemSubstitute::updateOrCreate(
                [
                    'bom_item_id' => $bomItem->id,
                    'substitute_part_id' => $subPart->id,
                ],
                [
                    'ratio' => $row['ratio'] ?? 1,
                    'priority' => $row['priority'] ?? 1,
                    'status' => $row['status'] ?? 'active',
                    'notes' => $row['notes'] ?? null,
                ]
            );

            $this->rowCount++;
        }
    }

    protected function addFailure($row, $message)
    {
        $this->failures[] = (object) [
            'row' => fn() => $row,
            'errors' => fn() => [$message],
        ];
    }

    public function failures()
    {
        return $this->failures;
    }
}
