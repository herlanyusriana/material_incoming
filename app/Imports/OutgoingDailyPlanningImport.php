<?php

namespace App\Imports;

use App\Models\GciPart;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class OutgoingDailyPlanningImport implements ToCollection
{
    /** @var list<string> */
    public array $dates = [];

    /** @var array<int, array{row_no:int|null, production_line:string, part_no:string, gci_part_id:int|null, cells:array<string, array{seq:int|null, qty:int|null}>}> */
    public array $rows = [];

    /** @var list<string> */
    public array $failures = [];

    /** @var list<string> */
    public array $createdParts = [];

    /** Optional date range for numeric column headers (1, 2, 3...) */
    public ?Carbon $planDateFrom = null;
    public ?Carbon $planDateTo = null;

    public function __construct(?Carbon $planDateFrom = null, ?Carbon $planDateTo = null)
    {
        $this->planDateFrom = $planDateFrom;
        $this->planDateTo = $planDateTo;
    }

    /**
     * Parse date column header.
     *
     * Supported examples:
     * - "2026-01-30 Seq"
     * - "2026/01/30 Qty"
     * - "2026 01 30 seq"
     * - "Seq 2026-01-30"
     */
    private function parseDateColumnHeader(string $raw): ?array
    {
        $v = $raw;
        $v = str_replace("\u{00A0}", ' ', $v); // NBSP
        $v = str_replace("\u{200B}", '', $v); // zero-width space
        $v = str_replace("\u{FEFF}", '', $v); // BOM
        $v = trim($v);
        if ($v === '') {
            return null;
        }

        $v = str_replace('_', ' ', $v);
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;

        if (preg_match('/^(?<y>\d{4})[-\/ ](?<m>\d{1,2})[-\/ ](?<d>\d{1,2})\s+(?<kind>seq|qty)$/i', $v, $m)) {
            $date = sprintf('%04d-%02d-%02d', (int) $m['y'], (int) $m['m'], (int) $m['d']);
            return [$date, strtolower($m['kind'])];
        }

        if (preg_match('/^(?<d>\d{1,2})[-\/ ](?<m>\d{1,2})[-\/ ](?<y>\d{4})\s+(?<kind>seq|qty)$/i', $v, $m)) {
            $date = sprintf('%04d-%02d-%02d', (int) $m['y'], (int) $m['m'], (int) $m['d']);
            return [$date, strtolower($m['kind'])];
        }

        if (preg_match('/^(?<kind>seq|qty)\s+(?<y>\d{4})[-\/ ](?<m>\d{1,2})[-\/ ](?<d>\d{1,2})$/i', $v, $m)) {
            $date = sprintf('%04d-%02d-%02d', (int) $m['y'], (int) $m['m'], (int) $m['d']);
            return [$date, strtolower($m['kind'])];
        }

        if (preg_match('/^(?<kind>seq|qty)\s+(?<d>\d{1,2})[-\/ ](?<m>\d{1,2})[-\/ ](?<y>\d{4})$/i', $v, $m)) {
            $date = sprintf('%04d-%02d-%02d', (int) $m['y'], (int) $m['m'], (int) $m['d']);
            return [$date, strtolower($m['kind'])];
        }

        return null;
    }

    private function norm(string $value): string
    {
        $v = (string) $value;
        $v = str_replace("\u{00A0}", ' ', $v); // NBSP
        $v = str_replace("\u{200B}", '', $v); // zero-width space
        $v = str_replace("\u{FEFF}", '', $v); // BOM
        $v = str_replace('_', ' ', $v);
        $v = strtolower($v);
        // Turn punctuation into spaces so "Part No." matches "part no"
        $v = preg_replace('/[^a-z0-9]+/i', ' ', $v) ?? $v;
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;
        return trim($v);
    }

    private function normalizePartNo(mixed $value): string
    {
        $str = str_replace("\u{00A0}", ' ', (string) ($value ?? ''));
        $str = preg_replace('/\s+/', ' ', $str) ?? $str;
        return strtoupper(trim($str));
    }

    private function parseIntOrNull(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            $n = (int) round((float) $value);
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
        $n = (int) round((float) $str);
        return $n <= 0 ? null : $n;
    }

    private function rowValues(Collection $row): array
    {
        return array_values($row->all());
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        // Some users add a title row above the real header, so we scan a few first rows.
        $maxScan = min(10, $rows->count());
        $headerRow = null;
        $headers = [];
        $colIdx = [];
        $productionLineIdx = null;
        $partNoIdx = null;
        $noIdx = null;
        $headerRowPos = -1;

        for ($i = 0; $i < $maxScan; $i++) {
            $candidate = $rows->get($i);
            if (!$candidate) {
                continue;
            }

            $candidateHeaders = array_map(fn($v) => trim((string) $v), $this->rowValues($candidate));
            $candidateIdx = [];
            foreach ($candidateHeaders as $idx => $h) {
                $key = $this->norm($h);
                if ($key !== '') {
                    $candidateIdx[$key] = $idx;
                }
            }

            $candProductionLineIdx = $candidateIdx['production line'] ?? $candidateIdx['line'] ?? $candidateIdx['productionline'] ?? null;

            // New Priority: 'customer part no' > 'part no'
            $candPartNoIdx = $candidateIdx['customer part no'] ?? $candidateIdx['part no'] ?? $candidateIdx['part number'] ?? $candidateIdx['part'] ?? $candidateIdx['partno'] ?? null;

            $candNoIdx = $candidateIdx['no'] ?? $candidateIdx['number'] ?? $candidateIdx['#'] ?? $candidateIdx['no '] ?? null;

            // production_line is now optional — only part_no is required
            if ($candPartNoIdx !== null) {
                $headerRow = $candidate;
                $headers = $candidateHeaders;
                $colIdx = $candidateIdx;
                $productionLineIdx = $candProductionLineIdx; // may be null
                $partNoIdx = $candPartNoIdx;
                $noIdx = $candNoIdx;
                $headerRowPos = $i;
                break;
            }
        }

        if ($headerRow === null || $partNoIdx === null) {
            $first = $rows->first();
            $firstHeaders = $first ? array_map(fn($v) => trim((string) $v), $this->rowValues($first)) : [];
            $preview = array_slice(array_filter($firstHeaders, fn($v) => (string) $v !== ''), 0, 12);
            $this->failures[] =
                "Header kolom 'part_no/Part No' atau 'customer part no' tidak ditemukan. " .
                "Saran: download template Daily Planning. " .
                (!empty($preview) ? ("Header row pertama terbaca: " . implode(' | ', $preview)) : '');
            return;
        }

        // Drop all rows above the header row, and the header row itself.
        $rows = $rows->slice($headerRowPos + 1)->values();

        /** @var array<string, array{seq:int, qty:int}> $dateCols */
        $dateCols = [];
        foreach ($headers as $idx => $rawHeader) {
            $raw = trim((string) $rawHeader);
            if ($raw === '') {
                continue;
            }
            // Skip known non-date columns
            $normKey = $this->norm($raw);
            if (in_array($normKey, ['no', 'number', 'production line', 'line', 'productionline', 'part no', 'part number', 'part', 'partno', 'customer', 'customer part no', 'part name', 'model', 'status'], true)) {
                continue;
            }

            $parsed = $this->parseDateColumnHeader($raw);
            if ($parsed) {
                [$date, $kind] = $parsed;
                $dateCols[$date] ??= ['seq' => -1, 'qty' => -1];
                $dateCols[$date][$kind] = $idx;
                continue;
            }

            // Support numeric column headers (1, 2, 3...) mapped to plan date range
            if ($this->planDateFrom && preg_match('/^\d+$/', $raw)) {
                $dayOffset = (int) $raw - 1;
                if ($dayOffset >= 0) {
                    $date = $this->planDateFrom->copy()->addDays($dayOffset)->format('Y-m-d');
                    $dateCols[$date] ??= ['seq' => -1, 'qty' => -1];
                    $dateCols[$date]['qty'] = $idx;
                }
            }
        }

        $dates = array_keys($dateCols);
        sort($dates);
        $this->dates = $dates;

        $results = [];

        foreach ($rows as $index => $row) {
            $arr = $this->rowValues($row);
            $productionLine = $productionLineIdx !== null
                ? strtoupper(trim((string) ($arr[$productionLineIdx] ?? '')))
                : '';
            $partNo = $this->normalizePartNo($arr[$partNoIdx] ?? '');
            $rowNo = $this->parseIntOrNull($noIdx !== null ? ($arr[$noIdx] ?? null) : null);

            if ($partNo === '') {
                continue;
            }

            // Resolve part_no to a Customer Part ID or GCI Part ID (FG only)
            $gciPartIds = [];
            $customerPartId = null;

            if ($partNo !== '') {
                $customerPart = \App\Models\CustomerPart::query()
                    ->where('customer_part_no', $partNo)
                    ->first();

                if ($customerPart) {
                    $customerPartId = $customerPart->id;
                    if ($productionLine === '' && !empty($customerPart->line)) {
                        $productionLine = strtoupper(trim($customerPart->line));
                    }
                    $fgComponents = \App\Models\CustomerPartComponent::query()
                        ->where('customer_part_id', $customerPart->id)
                        ->whereHas('part', function ($q) {
                            $q->where('classification', 'FG');
                        })
                        ->get();

                    foreach ($fgComponents as $fgComponent) {
                        $gciPartIds[] = [
                            'id' => $fgComponent->gci_part_id,
                            'usage_qty' => (float) ($fgComponent->qty_per_unit ?? 1.0),
                        ];
                    }
                } else {
                    $cleanPartNo = str_replace(['-', ' ', '/', '.', '_'], '', $partNo);
                    $gciPart = GciPart::query()
                        ->where('classification', 'FG')
                        ->where(function ($q) use ($partNo, $cleanPartNo) {
                            $q->where('part_no', $partNo)
                                ->orWhere('part_no', $cleanPartNo);
                        })
                        ->first();

                    if ($gciPart) {
                        $gciPartIds[] = [
                            'id' => $gciPart->id,
                            'usage_qty' => 1.0,
                        ];
                    } else {
                        $this->createdParts[] = $partNo . " (UNMAPPED - Not found in Customer Parts or GCI Parts)";
                    }
                }
            }

            $cells = [];
            foreach ($dateCols as $date => $idxs) {
                $seq = ($idxs['seq'] ?? -1) >= 0 ? $this->parseIntOrNull($arr[$idxs['seq']] ?? null) : null;
                $qty = ($idxs['qty'] ?? -1) >= 0 ? $this->parseIntOrNull($arr[$idxs['qty']] ?? null) : null;
                if ($seq === null && $qty === null) {
                    continue;
                }
                $cells[$date] = ['seq' => $seq, 'qty' => $qty];
            }

            $partData = !empty($gciPartIds) ? $gciPartIds : [['id' => null, 'usage_qty' => 1.0]];

            foreach ($partData as $pd) {
                $gciId = $pd['id'];
                $key = "{$productionLine}|{$partNo}|" . ($gciId ?? 'NULL') . "|" . ($customerPartId ?? 'NULL');

                if (!isset($results[$key])) {
                    $results[$key] = [
                        'row_no' => $rowNo,
                        'production_line' => $productionLine,
                        'part_no' => $partNo,
                        'customer_part_id' => $customerPartId,
                        'gci_part_id' => $gciId,
                        'usage_qty' => $pd['usage_qty'],
                        'cells' => [],
                    ];
                }

                foreach ($cells as $date => $cell) {
                    if (!isset($results[$key]['cells'][$date])) {
                        $results[$key]['cells'][$date] = ['seq' => $cell['seq'], 'qty' => 0];
                    }
                    if ($cell['qty'] !== null) {
                        $results[$key]['cells'][$date]['qty'] += (int) $cell['qty'];
                    }
                    if ($results[$key]['cells'][$date]['seq'] === null) {
                        $results[$key]['cells'][$date]['seq'] = $cell['seq'];
                    }
                }
            }
        }
        $this->rows = array_values($results);
    }

    public function dateFrom(): ?Carbon
    {
        if ($this->dates === []) {
            return null;
        }
        return Carbon::createFromFormat('Y-m-d', $this->dates[0]);
    }

    public function dateTo(): ?Carbon
    {
        if ($this->dates === []) {
            return null;
        }
        return Carbon::createFromFormat('Y-m-d', $this->dates[count($this->dates) - 1]);
    }
}
