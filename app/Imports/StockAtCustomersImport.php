<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\GciPart;
use App\Models\StockAtCustomer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockAtCustomersImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $rowCount = 0;
    public int $skippedRows = 0;
    public array $failures = [];

    public function __construct(private readonly string $period)
    {
    }

    private function normalizeUpper(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        $value = strtoupper($value);
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

    public function collection(Collection $rows)
    {
        foreach ($rows as $idx => $row) {
            try {
                $data = is_array($row) ? $row : $row->toArray();

                $customerName = $this->normalizeTrim($data['customer'] ?? null);
                $partNo = $this->normalizeUpper($data['part_no'] ?? null);

                if (!$customerName || !$partNo) {
                    $this->skippedRows++;
                    continue;
                }

                $customer = Customer::query()
                    ->whereRaw('LOWER(name) = ?', [strtolower($customerName)])
                    ->first();

                if (!$customer) {
                    $this->failures[] = "Row " . ($idx + 2) . ": customer [{$customerName}] tidak ditemukan.";
                    continue;
                }

                $partName = $this->normalizeTrim($data['part_name'] ?? null);
                $model = $this->normalizeTrim($data['model'] ?? null);
                $status = $this->normalizeTrim($data['status'] ?? null);

                $gciPart = GciPart::query()->where('part_no', $partNo)->first();
                if (!$gciPart) {
                    $gciPart = GciPart::query()->create([
                        'part_no' => $partNo,
                        'part_name' => $partName,
                        'model' => $model,
                        'classification' => 'FG',
                        'status' => 'active',
                    ]);
                } else {
                    // Keep master data untouched; only store text columns in stock record.
                }

                $payload = [
                    'period' => $this->period,
                    'customer_id' => $customer->id,
                    'gci_part_id' => $gciPart->id,
                    'part_no' => $partNo,
                    'part_name' => $partName,
                    'model' => $model,
                    'status' => $status,
                ];

                for ($d = 1; $d <= 31; $d++) {
                    $key = (string) $d;
                    $raw = $data[$key] ?? null;
                    $qty = 0;
                    if ($raw !== null && $raw !== '') {
                        $qty = is_numeric($raw) ? (float) $raw : 0;
                    }
                    $payload['day_' . $d] = $qty;
                }

                StockAtCustomer::query()->updateOrCreate(
                    [
                        'period' => $this->period,
                        'customer_id' => $customer->id,
                        'part_no' => $partNo,
                    ],
                    $payload,
                );

                $this->rowCount++;
            } catch (\Throwable $e) {
                $this->failures[] = "Row " . ($idx + 2) . ": " . $e->getMessage();
            }
        }
    }
}

