<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CustomerPlanningUploadImport implements WithMultipleSheets
{
    public ?string $format = null; // weekly|monthly
    /** @var array<int, string> */
    public array $detectedHeaders = [];

    public function __construct(private readonly bool $includeWeekMap = true)
    {
    }

    /** @var array<int, array{customer_part_no:string, minggu:string, qty:float}> */
    public array $weeklyRows = [];

    /** @var array<int, array{customer_part_no:string, months:array<string, float>}> */
    public array $monthlyRows = [];

    /** @var array<int, array{month_header:string, minggu:string, ratio:float|null}> */
    public array $weekMapRows = [];

    public static function normalizeMonthKey(string $header): string
    {
        $raw = trim($header);
        if ($raw === '') {
            return '';
        }

        // Remove common suffixes like "Prod" and collapse whitespace.
        $raw = preg_replace('/\s+/', ' ', $raw) ?? $raw;
        $rawNoSuffix = preg_replace('/\s*prod\s*$/i', '', $raw) ?? $raw;
        $rawNoSuffix = trim($rawNoSuffix);

        // Already in YYYY-MM.
        if (preg_match('/^\d{4}-\d{2}$/', $rawNoSuffix)) {
            return $rawNoSuffix;
        }

        $monthMap = [
            'jan' => 1, 'january' => 1,
            'feb' => 2, 'february' => 2,
            'mar' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5,
            'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7,
            'aug' => 8, 'august' => 8,
            'sep' => 9, 'sept' => 9, 'september' => 9,
            'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'dec' => 12, 'december' => 12,
        ];

        // Formats like Dec'25, Dec 25, Dec'2025, December'25, etc.
        if (preg_match('/^(?<mon>[A-Za-z]{3,9})\s*\'?\s*(?<yy>\d{2}|\d{4})$/', $rawNoSuffix, $m)) {
            $monKey = strtolower($m['mon']);
            $month = $monthMap[$monKey] ?? ($monthMap[substr($monKey, 0, 3)] ?? null);
            if ($month) {
                $yearRaw = $m['yy'];
                $year = strlen($yearRaw) === 2 ? (2000 + (int) $yearRaw) : (int) $yearRaw;
                return sprintf('%04d-%02d', $year, $month);
            }
        }

        // Formats like Dec'25 Prod (suffix already removed), but keep raw if not parseable.
        return $raw;
    }

    public function sheets(): array
    {
        $sheets = [
            0 => new class($this) implements ToCollection {
                public function __construct(private readonly CustomerPlanningUploadImport $parent)
                {
                }

                private function normHeader(string $value): string
                {
                    $value = str_replace("\u{00A0}", ' ', $value);
                    $value = strtolower(trim($value));
                    $value = preg_replace('/\s+/', ' ', $value) ?? $value;
                    $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;
                    $value = trim($value, '_');

                    return match ($value) {
                        'customer_part', 'customer_part_no', 'customer_part_number', 'customer_partnum', 'customer_part_no_' => 'customer_part_no',
                        'minggu', 'week', 'week_no', 'week_number', 'period' => 'minggu',
                        'qty', 'quantity', 'qty_pcs', 'qty_pc' => 'qty',
                        'part_number', 'part_no', 'partnum', 'partnumber' => 'part_number',
                        'biz_type', 'biztype' => 'biz_type',
                        default => $value,
                    };
                }

                private function normalizePartNo(mixed $value): string
                {
                    $str = str_replace("\u{00A0}", ' ', (string) ($value ?? ''));
                    $str = preg_replace('/\s+/', ' ', $str) ?? $str;
                    return strtoupper(trim($str));
                }

                private function rowValues(Collection $row): array
                {
                    return array_values($row->all());
                }

                private function parseQty(mixed $value): float
                {
                    if ($value === null) {
                        return 0.0;
                    }
                    if (is_numeric($value)) {
                        return (float) $value;
                    }
                    $str = trim((string) $value);
                    if ($str === '' || $str === '-') {
                        return 0.0;
                    }
                    $str = str_replace([',', ' '], ['', ''], $str);
                    return is_numeric($str) ? (float) $str : 0.0;
                }

                public function collection(Collection $rows): void
                {
                    if ($rows->isEmpty()) {
                        return;
                    }

                    $headerRow = $rows->shift();
                    if (!$headerRow) {
                        return;
                    }

                    $headers = array_map(fn ($v) => trim((string) $v), $this->rowValues($headerRow));
                    $this->parent->detectedHeaders = array_values(array_filter($headers, fn ($h) => $h !== ''));
                    $headerIndex = [];
                    foreach ($headers as $idx => $h) {
                        $key = $this->normHeader($h);
                        if ($key !== '') {
                            $headerIndex[$key] = $idx;
                        }
                    }

                    $weeklyPartKey = isset($headerIndex['customer_part_no']) ? 'customer_part_no' : (isset($headerIndex['part_number']) ? 'part_number' : null);
                    $isWeekly = $weeklyPartKey !== null && isset($headerIndex['minggu']) && isset($headerIndex['qty']);

                    $monthlyPartKey = isset($headerIndex['part_number']) ? 'part_number' : (isset($headerIndex['customer_part_no']) ? 'customer_part_no' : null);
                    $isMonthly = $monthlyPartKey !== null;

                    if ($isWeekly) {
                        $this->parent->format = 'weekly';
                        foreach ($rows as $row) {
                            $arr = $this->rowValues($row);
                            $customerPartNo = $this->normalizePartNo($arr[$headerIndex[$weeklyPartKey]] ?? '');
                            $minggu = strtoupper(trim((string) ($arr[$headerIndex['minggu']] ?? '')));
                            $qty = $this->parseQty($arr[$headerIndex['qty']] ?? null);
                            if ($customerPartNo === '' && $minggu === '' && $qty === 0.0) {
                                continue;
                            }
                            $this->parent->weeklyRows[] = [
                                'customer_part_no' => $customerPartNo,
                                'minggu' => $minggu,
                                'qty' => $qty,
                            ];
                        }
                        return;
                    }

                    if ($isMonthly) {
                        $partNumberIdx = $headerIndex[$monthlyPartKey];
                        $bizTypeIdx = $headerIndex['biz_type'] ?? null;

                        $monthColumns = [];
                        foreach ($headers as $idx => $rawHeader) {
                            $clean = trim((string) $rawHeader);
                            if ($idx === $partNumberIdx || ($bizTypeIdx !== null && $idx === $bizTypeIdx)) {
                                continue;
                            }
                            if ($clean === '') {
                                continue;
                            }
                            // Treat any header that contains "Prod" as month demand column.
                            if (stripos($clean, 'prod') !== false) {
                                $monthColumns[$clean] = $idx;
                                continue;
                            }
                            // Or explicit YYYY-MM, or parseable month key.
                            $monthKey = CustomerPlanningUploadImport::normalizeMonthKey($clean);
                            if (preg_match('/^\d{4}-\d{2}$/', $clean) || preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
                                $monthColumns[$clean] = $idx;
                            }
                        }

                        // Monthly format must have at least 1 month column.
                        if (count($monthColumns) < 1) {
                            return;
                        }

                        $this->parent->format = 'monthly';
                        foreach ($rows as $row) {
                            $arr = $this->rowValues($row);
                            $customerPartNo = $this->normalizePartNo($arr[$partNumberIdx] ?? '');
                            if ($customerPartNo === '') {
                                continue;
                            }
                            $months = [];
                            foreach ($monthColumns as $monthHeader => $colIdx) {
                                $monthKey = CustomerPlanningUploadImport::normalizeMonthKey($monthHeader);
                                $months[$monthKey] = ($months[$monthKey] ?? 0) + $this->parseQty($arr[$colIdx] ?? null);
                            }
                            $this->parent->monthlyRows[] = [
                                'customer_part_no' => $customerPartNo,
                                'months' => $months,
                            ];
                        }
                        return;
                    }

                    // Unknown format; keep null and let controller throw a helpful error.
                }
            },
        ];

        if (!$this->includeWeekMap) {
            return $sheets;
        }

        $sheets[1] = new class($this) implements ToCollection {
                public function __construct(private readonly CustomerPlanningUploadImport $parent)
                {
                }

                private function rowValues(Collection $row): array
                {
                    return array_values($row->all());
                }

                private function parseRatio(mixed $value): ?float
                {
                    if ($value === null) {
                        return null;
                    }
                    if (is_numeric($value)) {
                        $num = (float) $value;
                        return $num > 0 ? $num : null;
                    }
                    $str = trim((string) $value);
                    if ($str === '' || $str === '-') {
                        return null;
                    }
                    $str = str_replace([',', ' '], ['', ''], $str);
                    if (!is_numeric($str)) {
                        return null;
                    }
                    $num = (float) $str;
                    return $num > 0 ? $num : null;
                }

                public function collection(Collection $rows): void
                {
                    if ($rows->isEmpty()) {
                        return;
                    }

                    $headerRow = $rows->shift();
                    if (!$headerRow) {
                        return;
                    }

                    $headers = array_map(fn ($v) => strtolower(trim((string) $v)), $this->rowValues($headerRow));
                    $idxMonth = array_search('month_key', $headers, true);
                    if ($idxMonth === false) {
                        $idxMonth = array_search('month_header', $headers, true);
                    }
                    if ($idxMonth === false) {
                        $idxMonth = array_search('month', $headers, true);
                    }
                    $idxMinggu = array_search('minggu', $headers, true);
                    $idxRatio = array_search('ratio', $headers, true);

                    if ($idxMonth === false || $idxMinggu === false || $idxRatio === false) {
                        return;
                    }

                    foreach ($rows as $row) {
                        $arr = $this->rowValues($row);
                        $monthHeader = trim((string) ($arr[$idxMonth] ?? ''));
                        $monthKey = CustomerPlanningUploadImport::normalizeMonthKey($monthHeader);
                        $minggu = strtoupper(trim((string) ($arr[$idxMinggu] ?? '')));
                        $ratio = $this->parseRatio($arr[$idxRatio] ?? null);
                        if ($monthKey === '' || $minggu === '') {
                            continue;
                        }
                        $this->parent->weekMapRows[] = [
                            'month_header' => $monthKey,
                            'minggu' => $minggu,
                            'ratio' => $ratio,
                        ];
                    }
                }
            };

        return $sheets;
    }
}
