<?php

namespace App\Imports;

use App\Models\Machine;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class MachinesImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsEmptyRows
{
    private ?array $existingCodes = null;
    private array $seenCodes = [];

    public function model(array $row)
    {
        $this->loadExistingCodes();

        $code = strtoupper(trim((string) ($row['code'] ?? '')));

        if (isset($this->seenCodes[$code])) {
            throw new \RuntimeException("Import dibatalkan: ada data duplikat di file (code={$code}).");
        }
        if (isset($this->existingCodes[$code])) {
            throw new \RuntimeException("Import dibatalkan: data sudah ada di sistem (code={$code}).");
        }

        $this->seenCodes[$code] = true;

        $isActive = strtolower(trim((string) ($row['is_active'] ?? 'yes')));

        $cycleTimeUnit = strtolower(trim((string) ($row['cycle_time_unit'] ?? 'seconds')));
        if (!in_array($cycleTimeUnit, ['seconds', 'minutes', 'hours'], true)) {
            $cycleTimeUnit = 'seconds';
        }

        return new Machine([
            'code' => $code,
            'name' => trim((string) ($row['name'] ?? '')),
            'group_name' => trim((string) ($row['group_name'] ?? '')) ?: null,
            'cycle_time' => (float) ($row['cycle_time'] ?? 0),
            'cycle_time_unit' => $cycleTimeUnit,
            'is_active' => in_array($isActive, ['yes', '1', 'true', 'active'], true),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'group_name' => 'nullable|string|max:255',
            'cycle_time' => 'nullable|numeric|min:0',
            'cycle_time_unit' => 'nullable|string|in:seconds,minutes,hours',
            'is_active' => 'nullable|string',
        ];
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function loadExistingCodes(): void
    {
        if ($this->existingCodes !== null) {
            return;
        }

        $codes = [];
        Machine::select('code')
            ->orderBy('id')
            ->chunk(1000, function ($machines) use (&$codes) {
                foreach ($machines as $machine) {
                    $codes[strtoupper(trim($machine->code))] = true;
                }
            });

        $this->existingCodes = $codes;
    }
}
