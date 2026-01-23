<?php

namespace App\Imports;

use App\Models\Truck;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TrucksImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $rowCount = 0;
    public int $skippedRows = 0;
    public array $failures = [];

    private function normalizeUpper(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = strtoupper(trim((string) $value));
        return $value === '' ? null : $value;
    }

    private function normalizeTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function normalizeStatus(?string $value): string
    {
        $raw = strtolower(trim((string) ($value ?? '')));
        return match ($raw) {
            'available', 'ready' => 'available',
            'in-use', 'inuse', 'in_use', 'used' => 'in-use',
            'maintenance', 'maint' => 'maintenance',
            default => 'available',
        };
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $idx => $row) {
            try {
                $data = is_array($row) ? $row : $row->toArray();

                $plateNo = $this->normalizeUpper($data['plate_no'] ?? null);
                if (!$plateNo) {
                    $this->skippedRows++;
                    continue;
                }

                $payload = [
                    'plate_no' => $plateNo,
                    'type' => $this->normalizeTrim($data['type'] ?? null),
                    'capacity' => $this->normalizeTrim($data['capacity'] ?? null),
                    'status' => $this->normalizeStatus($data['status'] ?? null),
                ];

                Truck::query()->updateOrCreate(['plate_no' => $plateNo], $payload);
                $this->rowCount++;
            } catch (\Throwable $e) {
                $this->failures[] = "Row " . ($idx + 2) . ": " . $e->getMessage();
            }
        }
    }
}

