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

    private ?array $headingDayMap = null;

    public function __construct(private readonly string $period)
    {
    }

    /**
     * Build a map of heading key → day number (1-31) for the current period.
     * Handles: plain day numbers ("1", "01"), date strings ("2026-03-01", "2026_03_01"),
     * and Excel serial date numbers that WithHeadingRow turns into integer keys.
     */
    private function buildHeadingDayMap(array $keys): array
    {
        $map = [];
        $knownTextKeys = ['customer', 'part_no', 'part_name', 'model', 'status'];
        $periodYear = (int) substr($this->period, 0, 4);
        $periodMonth = (int) substr($this->period, 5, 2);
        $daysInMonth = \Carbon\Carbon::create($periodYear, $periodMonth, 1)->daysInMonth;

        foreach ($keys as $key) {
            $k = (string) $key;

            // Skip known non-date columns
            if (in_array($k, $knownTextKeys, true)) {
                continue;
            }

            // 1) Plain day number: "1"-"31" or "01"-"31"
            if (preg_match('/^0*(\d{1,2})$/', $k, $m)) {
                $day = (int) $m[1];
                if ($day >= 1 && $day <= $daysInMonth && !isset($map[$k])) {
                    // Only accept if it's clearly a day number (1-31), not a serial date (>31)
                    if ($day <= 31) {
                        $map[$k] = $day;
                        continue;
                    }
                }
            }

            // 2) Date string variants: "2026-03-01", "2026_03_01"
            $normalized = str_replace('_', '-', $k);
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $normalized, $m)) {
                $y = (int) $m[1];
                $mo = (int) $m[2];
                $day = (int) $m[3];
                if ($y === $periodYear && $mo === $periodMonth && $day >= 1 && $day <= $daysInMonth) {
                    $map[$k] = $day;
                    continue;
                }
            }

            // 3) Excel serial date number (integer > 31, typically 40000-60000 range)
            if (is_numeric($k) && (int) $k > 31) {
                try {
                    $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) $k);
                    $y = (int) $dateObj->format('Y');
                    $mo = (int) $dateObj->format('m');
                    $day = (int) $dateObj->format('j');
                    if ($y === $periodYear && $mo === $periodMonth && $day >= 1 && $day <= $daysInMonth) {
                        $map[$k] = $day;
                        continue;
                    }
                } catch (\Throwable $e) {
                    // Not a valid serial date
                }
            }
        }

        return $map;
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
        $periodYear = (int) substr($this->period, 0, 4);
        $periodMonth = (int) substr($this->period, 5, 2);

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
                }

                // Build heading→day map once from the first data row's keys
                if ($this->headingDayMap === null) {
                    $this->headingDayMap = $this->buildHeadingDayMap(array_keys($data));
                }

                // Insert/update one row per day (row-per-date schema)
                foreach ($this->headingDayMap as $heading => $dayNum) {
                    $raw = $data[$heading] ?? null;
                    $qty = 0;
                    if ($raw !== null && $raw !== '') {
                        $qty = is_numeric($raw) ? (float) $raw : 0;
                    }

                    $stockDate = sprintf('%04d-%02d-%02d', $periodYear, $periodMonth, $dayNum);

                    if ($qty == 0) {
                        // Delete existing zero rows to keep table lean
                        StockAtCustomer::query()
                            ->where('stock_date', $stockDate)
                            ->where('customer_id', $customer->id)
                            ->where('part_no', $partNo)
                            ->delete();
                        continue;
                    }

                    StockAtCustomer::query()->updateOrCreate(
                        [
                            'stock_date' => $stockDate,
                            'customer_id' => $customer->id,
                            'part_no' => $partNo,
                        ],
                        [
                            'gci_part_id' => $gciPart->id,
                            'part_name' => $partName,
                            'model' => $model,
                            'status' => $status,
                            'qty' => $qty,
                        ],
                    );
                }

                $this->rowCount++;
            } catch (\Throwable $e) {
                $this->failures[] = "Row " . ($idx + 2) . ": " . $e->getMessage();
            }
        }
    }
}
