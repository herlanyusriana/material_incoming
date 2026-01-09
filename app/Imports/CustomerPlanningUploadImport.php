<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CustomerPlanningUploadImport implements WithMultipleSheets
{
    public ?string $format = null; // weekly|monthly

    /** @var array<int, array{customer_part_no:string, minggu:string, qty:float}> */
    public array $weeklyRows = [];

    /** @var array<int, array{customer_part_no:string, months:array<string, float>}> */
    public array $monthlyRows = [];

    /** @var array<int, array{month_header:string, minggu:string, ratio:float}> */
    public array $weekMapRows = [];

    public function sheets(): array
    {
        return [
            0 => new class($this) implements ToCollection {
                public function __construct(private readonly CustomerPlanningUploadImport $parent)
                {
                }

                private function normHeader(string $value): string
                {
                    return strtolower(trim($value));
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

                    $headers = $headerRow->map(fn ($v) => trim((string) $v))->all();
                    $headerIndex = [];
                    foreach ($headers as $idx => $h) {
                        $key = $this->normHeader($h);
                        if ($key !== '') {
                            $headerIndex[$key] = $idx;
                        }
                    }

                    $isWeekly = isset($headerIndex['customer_part_no']) && isset($headerIndex['minggu']) && isset($headerIndex['qty']);
                    $isMonthly = isset($headerIndex['part number']) || isset($headerIndex['part_number']);

                    if ($isWeekly) {
                        $this->parent->format = 'weekly';
                        foreach ($rows as $row) {
                            $arr = $row->all();
                            $customerPartNo = strtoupper(trim((string) ($arr[$headerIndex['customer_part_no']] ?? '')));
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
                        $this->parent->format = 'monthly';

                        $partNumberKey = isset($headerIndex['part number']) ? 'part number' : 'part_number';
                        $bizTypeIdx = $headerIndex['biz type'] ?? $headerIndex['biz_type'] ?? null;
                        $partNumberIdx = $headerIndex[$partNumberKey];

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
                            }
                        }

                        foreach ($rows as $row) {
                            $arr = $row->all();
                            $customerPartNo = strtoupper(trim((string) ($arr[$partNumberIdx] ?? '')));
                            if ($customerPartNo === '') {
                                continue;
                            }
                            $months = [];
                            foreach ($monthColumns as $monthHeader => $colIdx) {
                                $months[$monthHeader] = $this->parseQty($arr[$colIdx] ?? null);
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
            1 => new class($this) implements ToCollection {
                public function __construct(private readonly CustomerPlanningUploadImport $parent)
                {
                }

                private function parseRatio(mixed $value): ?float
                {
                    if ($value === null) {
                        return null;
                    }
                    if (is_numeric($value)) {
                        return (float) $value;
                    }
                    $str = trim((string) $value);
                    if ($str === '' || $str === '-') {
                        return null;
                    }
                    $str = str_replace([',', ' '], ['', ''], $str);
                    return is_numeric($str) ? (float) $str : null;
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

                    $headers = $headerRow->map(fn ($v) => strtolower(trim((string) $v)))->all();
                    $idxMonth = array_search('month_header', $headers, true);
                    if ($idxMonth === false) {
                        $idxMonth = array_search('month', $headers, true);
                    }
                    $idxMinggu = array_search('minggu', $headers, true);
                    $idxRatio = array_search('ratio', $headers, true);

                    if ($idxMonth === false || $idxMinggu === false || $idxRatio === false) {
                        return;
                    }

                    foreach ($rows as $row) {
                        $arr = $row->all();
                        $monthHeader = trim((string) ($arr[$idxMonth] ?? ''));
                        $minggu = strtoupper(trim((string) ($arr[$idxMinggu] ?? '')));
                        $ratio = $this->parseRatio($arr[$idxRatio] ?? null);
                        if ($monthHeader === '' || $minggu === '' || $ratio === null) {
                            continue;
                        }
                        $this->parent->weekMapRows[] = [
                            'month_header' => $monthHeader,
                            'minggu' => $minggu,
                            'ratio' => $ratio,
                        ];
                    }
                }
            },
        ];
    }
}

