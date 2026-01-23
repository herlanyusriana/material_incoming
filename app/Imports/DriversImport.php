<?php

namespace App\Imports;

use App\Models\Driver;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DriversImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $rowCount = 0;
    public int $skippedRows = 0;
    public array $failures = [];

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
            'on-delivery', 'ondelivery', 'on_delivery', 'delivery' => 'on-delivery',
            'off', 'inactive' => 'off',
            default => 'available',
        };
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $idx => $row) {
            try {
                $data = is_array($row) ? $row : $row->toArray();

                $name = $this->normalizeTrim($data['name'] ?? null);
                if (!$name) {
                    $this->skippedRows++;
                    continue;
                }

                $phone = $this->normalizeTrim($data['phone'] ?? null);

                $payload = [
                    'name' => $name,
                    'phone' => $phone,
                    'license_type' => $this->normalizeTrim($data['license_type'] ?? null),
                    'status' => $this->normalizeStatus($data['status'] ?? null),
                ];

                // Best-effort: update by (name + phone) when phone exists, else by name only.
                $keys = ['name' => $name];
                if ($phone) {
                    $keys['phone'] = $phone;
                }

                Driver::query()->updateOrCreate($keys, $payload);
                $this->rowCount++;
            } catch (\Throwable $e) {
                $this->failures[] = "Row " . ($idx + 2) . ": " . $e->getMessage();
            }
        }
    }
}

