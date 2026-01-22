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

    /** @var list<string> */
    public array $failures = [];

    /** @var list<string> */
    public array $createdParts = [];


    private function norm(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
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

        $headerRow = $rows->shift();
        if (!$headerRow) {
            return;
        }

        $headers = array_map(fn ($v) => trim((string) $v), $this->rowValues($headerRow));
        $colIdx = [];
        foreach ($headers as $idx => $h) {
            $key = $this->norm($h);
            if ($key !== '') {
                $colIdx[$key] = $idx;
            }
        }

        $productionLineIdx = $colIdx['production_line'] ?? $colIdx['production line'] ?? null;
        $partNoIdx = $colIdx['part_no'] ?? $colIdx['part no'] ?? $colIdx['part number'] ?? null;
        $noIdx = $colIdx['no'] ?? $colIdx['#'] ?? null;

        if ($productionLineIdx === null || $partNoIdx === null) {
            $this->failures[] = "Header kolom 'production_line' atau 'part_no' tidak ditemukan.";
            return;
        }

        /** @var array<string, array{seq:int, qty:int}> $dateCols */
        $dateCols = [];
        foreach ($headers as $idx => $rawHeader) {
            $raw = trim((string) $rawHeader);
            if ($raw === '') {
                continue;
            }
            $norm = $this->norm($raw);
            if (preg_match('/^(?<date>\d{4}-\d{2}-\d{2})\s+(?<kind>seq|qty)$/i', $norm, $m)) {
                $date = $m['date'];
                $kind = strtolower($m['kind']);
                $dateCols[$date] ??= ['seq' => -1, 'qty' => -1];
                $dateCols[$date][$kind] = $idx;
            }
        }

        $dates = array_keys($dateCols);
        sort($dates);
        $this->dates = $dates;

        foreach ($rows as $index => $row) {
            $arr = $this->rowValues($row);
            $productionLine = strtoupper(trim((string) ($arr[$productionLineIdx] ?? '')));
            $partNo = $this->normalizePartNo($arr[$partNoIdx] ?? '');
            $rowNo = $this->parseIntOrNull($noIdx !== null ? ($arr[$noIdx] ?? null) : null);

            // Use logical row number (2 for header + index + 1)
            $excelRow = $index + 2; 

            if ($productionLine === '' && $partNo === '') {
                continue;
            }

            // Resolve part_no to a GCI Part ID (FG only)
            // First try direct GCI Part lookup, then customer part mapping
            $gciPartId = null;
            
            if ($partNo !== '') {
                // Try direct GCI Part lookup (FG only)
                $gciPart = GciPart::query()
                    ->where('part_no', $partNo)
                    ->where('classification', 'FG')
                    ->first();
                
                if ($gciPart) {
                    $gciPartId = $gciPart->id;
                } else {
                    // Try customer part mapping lookup
                    $customerPart = \App\Models\CustomerPart::query()
                        ->where('customer_part_no', $partNo)
                        ->where('status', 'active')
                        ->with(['components.part' => function($q) {
                            $q->where('classification', 'FG');
                        }])
                        ->first();
                    
                    if ($customerPart) {
                        $fgComponents = $customerPart->components->filter(fn($c) => 
                            $c->part && $c->part->classification === 'FG'
                        );
                        
                        if ($fgComponents->isEmpty()) {
                            // Auto-create GCI Part as fallback
                            $gciPart = GciPart::create([
                                'part_no' => $partNo,
                                'part_name' => 'AUTO-CREATED IMPORT',
                                'classification' => 'FG',
                                'status' => 'active',
                            ]);
                            $gciPartId = $gciPart->id;
                            $this->createdParts[] = $partNo . " (Fallback from CustomerPart)";
                        } else {
                            // Use the first FG component
                            $gciPartId = $fgComponents->first()->part_id;
                        }
                    } else {
                        // Auto-create GCI Part
                        $gciPart = GciPart::create([
                            'part_no' => $partNo,
                            'part_name' => 'AUTO-CREATED IMPORT',
                            'classification' => 'FG',
                            'status' => 'active',
                        ]);
                        $gciPartId = $gciPart->id;
                        $this->createdParts[] = $partNo;
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

            $this->rows[] = [
                'row_no' => $rowNo,
                'production_line' => $productionLine,
                'part_no' => $partNo,
                'gci_part_id' => $gciPartId,
                'cells' => $cells,
            ];
        }
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

