<?php

namespace App\Imports;

use App\Models\CustomerPart;
use App\Models\CustomerPartComponent;
use App\Models\GciPart;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Parses a PT LG plan Excel file with one column per month.
 *
 * Expected shape (cols per month):
 *   | part_code | 2026-09 | 2026-10 | 2026-11 |
 *   |-----------|---------|---------|---------|
 *   | ABC-123   | 5000    | 6000    | 4500    |
 *
 * Resolution rule ("cocok langsung + fallback mapping"):
 *   1. Look up the code directly against GCI part_no.
 *   2. Fall back to CustomerPart by customer_part_no -> FG GCI part via
 *      CustomerPartComponent.
 *   3. If neither resolves, mark the row `unmapped`.
 */
class ForecastPlanImport implements ToCollection
{
    /** @var list<array> */
    public array $rows = [];

    /** @var list<array> */
    public array $unmapped = [];

    /** @var list<string> */
    public array $failures = [];

    /** @var list<string> */
    public array $periods = [];

    /** @var array<int,array<string,int|float>> @protected memo of period extraction */
    protected array $periodMemo = [];

    /** @var array<string,int> @protected memo of path-relative resolved gci_part_id */
    protected array $resolvedCache = [];

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            $this->failures[] = 'File kosong.';
            return;
        }

        $header = $this->locateHeader($rows);
        if ($header === null) {
            $this->failures[] = 'Header kolom tidak ditemukan. Baris pertama harus memuat kolom part (part_code/customer_part_no) dan kolom bulan (YYYY-MM).';
            return;
        }

        [$headerRowPos, $colIdx, $partCodeIdx] = $header;

        $periodIdx = [];
        foreach ($colIdx as $key => $idx) {
            $period = $this->extractPeriodFromHeader($key);
            if ($period !== null) {
                $periodIdx[$period] = $idx;
            }
        }

        if (empty($periodIdx)) {
            $this->failures[] = 'Tidak ada kolom bulan (YYYY-MM) yang terdeteksi. Kolom header harus berupa periode seperti 2026-09.';
            return;
        }

        $this->periods = array_keys($periodIdx);
        sort($this->periods);

        // Skip rows above header and the header itself.
        $dataRows = $rows->slice($headerRowPos + 1)->values();

        foreach ($dataRows as $index => $row) {
            $arr = array_values($row->all());
            $partCode = $this->normalizeCode($arr[$partCodeIdx] ?? null);
            if ($partCode === '') {
                continue;
            }

            $rowNo = (string) ($index + $headerRowPos + 2); // 1-based Excel row number

            $quantities = [];
            foreach ($periodIdx as $period => $idx) {
                $qty = $this->parseQuantity($arr[$idx] ?? null);
                if ($qty !== null) {
                    $quantities[$period] = $qty;
                }
            }

            if (empty($quantities)) {
                continue;
            }

            $gciPartId = $this->resolvePart($partCode);

            $this->rows[] = [
                'row_no'          => $rowNo,
                'customer_part_no'=> $partCode,
                'gci_part_id'     => $gciPartId,
                'mapping_status'  => $gciPartId !== null ? 'mapped' : 'unmapped',
                'quantities'      => $quantities,
            ];
        }

        // Split unmapped rows + collect part names where possible.
        $this->unmapped = array_values(array_filter($this->rows, fn($r) => $r['gci_part_id'] === null));
    }

    /**
     * Find the header row. Looks for a part-code column plus one or more
     * YYYY-MM period columns within the first few rows (tolerating a title row).
     */
    private function locateHeader(Collection $rows): ?array
    {
        $maxScan = min(10, $rows->count());

        for ($i = 0; $i < $maxScan; $i++) {
            $candidate = $rows->get($i);
            if (!$candidate) {
                continue;
            }

            $values = array_values($candidate->all());
            $colIdx = [];
            $partCodeIdx = null;

            foreach ($values as $idx => $raw) {
                $key = $this->normalizeHeader((string) $raw);
                if ($key === '') {
                    continue;
                }
                if (!isset($colIdx[$key])) {
                    $colIdx[$key] = $idx;
                }

                $period = $this->extractPeriodFromHeader($key);
                if ($period !== null) {
                    continue;
                }

                if ($partCodeIdx === null && $this->isPartCodeHeader($key)) {
                    $partCodeIdx = $idx;
                }
            }

            if ($partCodeIdx === null) {
                continue;
            }

            // Confirm at least one period column exists.
            foreach ($colIdx as $key => $idx) {
                if ($this->extractPeriodFromHeader($key) !== null) {
                    return [$i, $colIdx, $partCodeIdx];
                }
            }
        }

        return null;
    }

    /**
     * Resolve a part code to a GCI part id (direct match, then CustomerPart mapping).
     */
    private function resolvePart(string $code): ?int
    {
        if (isset($this->resolvedCache[$code])) {
            return $this->resolvedCache[$code] ?: null;
        }

        // 1. Direct: GCI part_no
        $gci = GciPart::query()->where('part_no', $code)->first();
        if ($gci) {
            $this->resolvedCache[$code] = $gci->id;
            return $gci->id;
        }

        // 2. Fallback: CustomerPart (customer_part_no) -> its FG GCI part
        $customerPart = CustomerPart::query()->where('customer_part_no', $code)->first();
        if ($customerPart) {
            $fgComponent = CustomerPartComponent::query()
                ->where('customer_part_id', $customerPart->id)
                ->whereHas('part', fn($q) => $q->where('classification', 'FG'))
                ->first();

            if ($fgComponent) {
                $this->resolvedCache[$code] = $fgComponent->gci_part_id;
                return $fgComponent->gci_part_id;
            }
        }

        $this->resolvedCache[$code] = 0;
        return null;
    }

    private function isPartCodeHeader(string $key): bool
    {
        return in_array($key, [
            'part code', 'partcode', 'part no', 'part no.', 'part number', 'part',
            'customer part no', 'customerpartno', 'customer part', 'cust part no',
            'kode part', 'part gci', 'gci part no', 'gci part',
        ], true);
    }

    /**
     * Extract YYYY-MM from a normalized header key. Returns null if not a period.
     */
    private function extractPeriodFromHeader(string $key): ?string
    {
        if (isset($this->periodMemo[$key])) {
            return $this->periodMemo[$key];
        }

        $result = null;
        if (preg_match('/^(?<y>\d{4})[\-\/ ](?<m>\d{1,2})$/', $key, $m)) {
            $result = sprintf('%04d-%02d', (int) $m['y'], (int) $m['m']);
        } elseif (preg_match('/^(?<y>\d{4})(?<m>\d{2})$/', $key, $m)) {
            $result = sprintf('%04d-%02d', (int) $m['y'], (int) $m['m']);
        } elseif (preg_match('/^(?<m>\d{1,2})[\-\/ ](?<y>\d{4})$/', $key, $m)) {
            $result = sprintf('%04d-%02d', (int) $m['y'], (int) $m['m']);
        } elseif (preg_match('/^(?<y>\d{4})[\-\/ ](?<m>\d{2})[\-\/ ](?<d>\d{1,2})$/', $key, $m)) {
            // Full date column like "2026-09-01" -> treat as its month.
            $result = sprintf('%04d-%02d', (int) $m['y'], (int) $m['m']);
        }

        $this->periodMemo[$key] = $result;
        return $result;
    }

    private function normalizeHeader(string $value): string
    {
        $v = (string) $value;
        $v = str_replace("\u{00A0}", ' ', $v); // NBSP
        $v = str_replace("\u{200B}", '', $v); // zero-width space
        $v = str_replace("\u{FEFF}", '', $v); // BOM
        $v = str_replace('_', ' ', $v);
        $v = strtolower($v);
        $v = preg_replace('/[^a-z0-9]+/i', ' ', $v) ?? $v;
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;
        return trim($v);
    }

    private function normalizeCode(mixed $value): string
    {
        $str = (string) ($value ?? '');
        $str = str_replace("\u{00A0}", ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str) ?? $str;
        return strtoupper(trim($str));
    }

    private function parseQuantity(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            $n = (float) $value;
            return $n <= 0 ? null : $n;
        }
        $str = trim((string) $value);
        if ($str === '' || $str === '-') {
            return null;
        }
        $str = str_replace([',', ' '], ['', ''], $str);
        if (!is_numeric($str)) {
            return null;
        }
        $n = (float) $str;
        return $n <= 0 ? null : $n;
    }
}
