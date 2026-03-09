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

    private ?array $headingDateMap = null;

    /**
     * @param string $startDate  Start date in Y-m-d format (7-day window)
     */
    public function __construct(private readonly string $startDate)
    {
    }

    /**
     * Build a map of heading key → actual date string (Y-m-d) for the 7-day window.
     * Handles: date strings ("2026-03-09", "2026_03_09"),
     * Excel serial date numbers, and plain day numbers (matched to the 7-day range).
     */
    private function buildHeadingDateMap(array $keys): array
    {
        $map = [];
        $knownTextKeys = ['customer', 'part_no', 'part_name', 'model', 'status'];

        $start = \Carbon\CarbonImmutable::parse($this->startDate);
        // Build set of valid dates in the 7-day window
        $validDates = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $start->addDays($i);
            $validDates[$d->format('Y-m-d')] = true;
        }

        foreach ($keys as $key) {
            $k = (string) $key;

            // Skip known non-date columns
            if (in_array($k, $knownTextKeys, true)) {
                continue;
            }

            // 1) Date string variants: "2026-03-09", "2026_03_09"
            $normalized = str_replace('_', '-', $k);
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $normalized, $m)) {
                $dateStr = $m[1] . '-' . $m[2] . '-' . $m[3];
                if (isset($validDates[$dateStr])) {
                    $map[$k] = $dateStr;
                    continue;
                }
            }

            // 2) Excel serial date number (integer > 31, typically 40000-60000 range)
            if (is_numeric($k) && (int) $k > 31) {
                try {
                    $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) $k);
                    $dateStr = $dateObj->format('Y-m-d');
                    if (isset($validDates[$dateStr])) {
                        $map[$k] = $dateStr;
                        continue;
                    }
                } catch (\Throwable $e) {
                    // Not a valid serial date
                }
            }

            // 3) Plain day number: "9", "09" etc. — match against 7-day window
            if (preg_match('/^0*(\d{1,2})$/', $k, $m)) {
                $dayNum = (int) $m[1];
                foreach ($validDates as $dateStr => $_) {
                    $d = \Carbon\CarbonImmutable::parse($dateStr);
                    if ((int) $d->format('j') === $dayNum) {
                        $map[$k] = $dateStr;
                        break; // take the first match
                    }
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

                // Build heading→date map once from the first data row's keys
                if ($this->headingDateMap === null) {
                    $this->headingDateMap = $this->buildHeadingDateMap(array_keys($data));
                }

                // Insert/update one row per day (row-per-date schema)
                foreach ($this->headingDateMap as $heading => $stockDate) {
                    $raw = $data[$heading] ?? null;
                    $qty = 0;
                    if ($raw !== null && $raw !== '') {
                        $qty = is_numeric($raw) ? (float) $raw : 0;
                    }

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
