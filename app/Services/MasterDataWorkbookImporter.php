<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\BomItemSubstitute;
use App\Models\GciPartVendor;
use App\Models\Machine;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\Vendor;
use App\Models\NewSchema\Core\VendorPart;
use App\Support\Uom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Imports the "master data" workbook (sheets: master FG, master mtrl, BOM)
 * into the Laravel master-data / BOM schema.
 *
 * Contract (see docs/superpowers/plans/2026-09-10-master-data-workbook-import.md):
 * - Headers on row 5 of each sheet; column names matched fuzzily.
 * - UOM normalized via App\Support\Uom (PCS -> PCE; KGM/SHEET/ROLL preserved).
 * - Vendor names matched punctuation/case-insensitively; only genuinely
 *   missing vendors are created.
 * - BOM graph for workbook parents is rebuilt deterministically; every
 *   generic material line is linked to all supplier substitutes, with
 *   bom_items.incoming_part_id pointing at the matching vendor_parts row.
 * - The whole run is atomic: any error rolls back everything. A dry run
 *   performs all work inside a transaction that is always rolled back.
 */
class MasterDataWorkbookImporter
{
    private const HEADER_ROW = 5;

    /** @var array<string, string[]> accepted normalized header aliases per logical field */
    private const HEADER_ALIASES = [
        'part_no' => ['partno', 'partnumber', 'part', 'kodepart', 'fgpartno', 'mtrlpartno', 'materialno'],
        'part_name' => ['partname', 'partdescription', 'description', 'nama', 'namapart', 'namamaterial', 'namabarang'],
        'model' => ['model', 'tipe', 'typepart'],
        'uom' => ['uom', 'unit', 'satuan', 'uomcode', 'unitofmeasure'],
        'size' => ['size', 'ukuran', 'dimension', 'dimensi', 'spec', 'spesifikasi'],
        'supplier' => ['supplier', 'suplier', 'vendor', 'namasupplier', 'namavendor', 'suppliername', 'vendorname'],
        'supplier_part_no' => ['supplierpartno', 'supplierpartnumber', 'vendorpartno', 'kodepartsupplier', 'partnosupplier', 'partnovendor'],
        'generic_part_no' => ['genericpartno', 'genericpart', 'generic', 'basematerialpartno', 'mastermtrlno', 'materialpartno', 'mtrlno'],
        'parent' => ['parent', 'parentpart', 'parentpartno', 'parentassembly', 'assembly', 'assemblypartno', 'fg', 'fgpartno', 'output'],
        'wip' => ['wip', 'wippart', 'wippartno', 'wipno', 'wipoutput', 'intermediate'],
        'child' => ['child', 'childpart', 'childpartno', 'component', 'componentpart', 'componentpartno', 'materialpart', 'materialpartno'],
        'seq' => ['seq', 'sequence', 'step', 'line', 'lineno', 'linenumber', 'urutan', 'nostep'],
        'qty' => ['qty', 'quantity', 'usage', 'usageqty', 'consumption', 'consumptionqty', 'netrequired'],
        'source' => ['source', 'sourcetype', 'makeorbuy', 'makebuy', 'sourcecode'],
        'machine' => ['machine', 'machinename', 'mesin', 'namamesin'],
        'process' => ['process', 'processname', 'proses', 'operation', 'operasi'],
    ];

    /** @var array<string, int> normalized header key -> column index per sheet */
    private array $columnMap = [];

    public function import(string $path, bool $dryRun = false): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException("Workbook tidak ditemukan atau tidak bisa dibaca: {$path}");
        }

        $parsed = $this->parse($path);

        DB::beginTransaction();

        try {
            $result = $this->apply($parsed);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        if ($dryRun) {
            DB::rollBack();

            return $result;
        }

        DB::commit();

        return $result;
    }

    // ------------------------------------------------------------------
    // Parsing
    // ------------------------------------------------------------------

    private function parse(string $path): array
    {
        $book = IOFactory::load($path);

        $fgRows = $this->readSheet($book, ['master fg'], 'master FG');
        $mtrlRows = $this->readSheet($book, ['master mtrl', 'master material'], 'master mtrl');
        $bomRows = $this->readSheet($book, ['bom'], 'BOM');

        return ['fg' => $fgRows, 'mtrl' => $mtrlRows, 'bom' => $bomRows];
    }

    private function readSheet($book, array $names, string $label): array
    {
        $sheet = null;
        foreach ($names as $candidate) {
            $sheet = $this->findSheet($book, $candidate);
            if ($sheet !== null) {
                break;
            }
        }

        if ($sheet === null) {
            throw new \RuntimeException("Sheet '{$label}' tidak ditemukan di workbook.");
        }

        $this->columnMap = [];
        $headers = $this->readRow($sheet, self::HEADER_ROW);
        foreach ($headers as $index => $text) {
            $normalized = $this->normalizeKey($text);
            if ($normalized === '') {
                continue;
            }
            foreach (self::HEADER_ALIASES as $field => $aliases) {
                if (in_array($normalized, $aliases, true) && !isset($this->columnMap[$field])) {
                    $this->columnMap[$field] = $index;
                    break;
                }
            }
        }

        // Stop after this many consecutive empty rows.
        $emptyStreak = 0;
        $rows = [];
        for ($row = self::HEADER_ROW + 1; $row <= $sheet->getHighestRow(); $row++) {
            $data = $this->readRow($sheet, $row);

            if ($this->rowIsEmpty($data)) {
                $emptyStreak++;
                if ($emptyStreak >= 3) {
                    break;
                }
                continue;
            }
            $emptyStreak = 0;

            $rows[] = [
                'excel_row' => $row,
                'data' => $data,
            ];
        }

        return ['rows' => $rows, 'columns' => $this->columnMap];
    }

    private function findSheet($book, string $name): ?Worksheet
    {
        foreach ($book->getAllSheets() as $sheet) {
            if ($this->normalizeKey($sheet->getTitle()) === $this->normalizeKey($name)) {
                return $sheet;
            }
        }

        return null;
    }

    private function readRow(Worksheet $sheet, int $row): array
    {
        $highest = $sheet->getHighestDataColumn();

        return $sheet->rangeToArray("A{$row}:{$highest}{$row}", null, false, false, false)[0] ?? [];
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeKey(?string $text): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $text) ?? '');
    }

    private function cell(array $columns, string $field, array $data, int $excelRow): ?string
    {
        if (!isset($columns[$field])) {
            return null;
        }
        $value = $data[$columns[$field]] ?? null;
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeUom(?string $uom, array &$warnings, int $excelRow): ?string
    {
        if ($uom === null) {
            return null;
        }
        $canonical = Uom::canonical($uom);
        if ($canonical === null) {
            $warnings[] = "Baris {$excelRow}: UOM '{$uom}' tidak dikenal, dipakai apa adanya.";

            return strtoupper($uom);
        }

        return $canonical;
    }

    // ------------------------------------------------------------------
    // Applying
    // ------------------------------------------------------------------

    private function apply(array $parsed): array
    {
        $warnings = [];
        $counts = [
            'fg_parts' => 0,
            'generic_rm_parts' => 0,
            'wip_parts' => 0,
            'substitute_parts' => 0,
            'vendors_created' => 0,
            'machines_created' => 0,
            'boms' => 0,
            'bom_items' => 0,
            'substitutes_linked' => 0,
            'warnings' => &$warnings,
        ];

        // ---------- master FG ----------
        $fgParts = [];
        foreach ($parsed['fg']['rows'] as $row) {
            $columns = $parsed['fg']['columns'];
            $partNo = $this->partNo($this->cell($columns, 'part_no', $row['data'], $row['excel_row']));
            if ($partNo === null) {
                $warnings[] = "Baris {$row['excel_row']} (master FG): kolom Part No kosong, dilewati.";
                continue;
            }

            $part = $this->upsertPart($partNo, [
                'part_name' => $this->cell($columns, 'part_name', $row['data'], $row['excel_row']),
                'model' => $this->cell($columns, 'model', $row['data'], $row['excel_row']),
                'size' => $this->cell($columns, 'size', $row['data'], $row['excel_row']),
                'uom' => $this->normalizeUom($this->cell($columns, 'uom', $row['data'], $row['excel_row']), $warnings, $row['excel_row']),
            ], 'FG');

            $fgParts[$partNo] = $part;
            $counts['fg_parts']++;
        }

        // ---------- master mtrl ----------
        // Vendor alias registry: normalized alias -> Vendor model.
        $vendorsByAlias = [];
        // Generic part registry by normalized key (part_no or part_name).
        $genericByKey = [];

        $mtrlColumns = $parsed['mtrl']['columns'];
        $genericParts = [];   // part_no => GciPart (classification RM, no vendor)
        $substitutes = [];    // list of ['part' => GciPart, 'vendor' => Vendor, 'generic' => GciPart]

        foreach ($parsed['mtrl']['rows'] as $row) {
            $partNo = $this->partNo($this->cell($mtrlColumns, 'part_no', $row['data'], $row['excel_row']));
            $partName = $this->cell($mtrlColumns, 'part_name', $row['data'], $row['excel_row']);
            $supplier = $this->cell($mtrlColumns, 'supplier', $row['data'], $row['excel_row']);
            $supplierPartNo = $this->partNo($this->cell($mtrlColumns, 'supplier_part_no', $row['data'], $row['excel_row']));
            $genericPartNo = $this->partNo($this->cell($mtrlColumns, 'generic_part_no', $row['data'], $row['excel_row']));
            $uom = $this->normalizeUom($this->cell($mtrlColumns, 'uom', $row['data'], $row['excel_row']), $warnings, $row['excel_row']);
            $size = $this->cell($mtrlColumns, 'size', $row['data'], $row['excel_row']);

            if ($partNo === null && $supplierPartNo !== null) {
                $partNo = $supplierPartNo;
            }
            if ($partNo === null) {
                $warnings[] = "Baris {$row['excel_row']} (master mtrl): Part No kosong, dilewati.";
                continue;
            }

            if ($supplier === null) {
                // Generic stocked material
                $part = $this->upsertPart($partNo, [
                    'part_name' => $partName,
                    'size' => $size,
                    'uom' => $uom,
                ], 'RM');
                $genericParts[$partNo] = $part;
                $genericByKey[$this->nameKey($partName)] = $part;
                $counts['generic_rm_parts']++;

                continue;
            }

            // Supplier-specific substitute part
            if ($supplierPartNo === null) {
                $warnings[] = "Baris {$row['excel_row']} (master mtrl): supplier '{$supplier}' tanpa Supplier Part No, baris dianggap generic.";
                $part = $this->upsertPart($partNo, ['part_name' => $partName, 'size' => $size, 'uom' => $uom], 'RM');
                $genericParts[$partNo] = $part;
                $genericByKey[$this->nameKey($partName)] = $part;
                $counts['generic_rm_parts']++;

                continue;
            }

            $vendor = $this->resolveVendor($supplier, $vendorsByAlias, $counts);
            $part = $this->upsertPart($partNo, ['part_name' => $partName, 'size' => $size, 'uom' => $uom], 'RM');

            $vendorPart = VendorPart::updateOrCreate(
                ['gci_part_id' => $part->id, 'vendor_id' => $vendor->id],
                [
                    'vendor_part_no' => $supplierPartNo,
                    'vendor_part_name' => $partName,
                    'uom' => $uom,
                    'status' => 'active',
                ]
            );

            GciPartVendor::updateOrCreate(
                ['gci_part_id' => $part->id, 'vendor_id' => $vendor->id],
                [
                    'vendor_part_no' => $supplierPartNo,
                    'vendor_part_name' => $partName,
                    'uom' => $uom,
                    'status' => 'active',
                ]
            );

            // Resolve target generic part for substitute mapping
            $genericPart = null;
            if ($genericPartNo !== null) {
                $genericPart = $genericParts[$genericPartNo]
                    ?? GciPart::where('part_no', $genericPartNo)->first();
            }
            if ($genericPart === null && $partName !== null) {
                $genericPart = $genericByKey[$this->nameKey($partName)] ?? null;
            }
            if ($genericPart === null) {
                $warnings[] = "Baris {$row['excel_row']} (master mtrl): substitusi '{$partNo}' tidak menemukan material generic ({$genericPartNo}), tidak ditautkan ke BOM.";
            }

            $substitutes[] = [
                'part' => $part,
                'vendor' => $vendor,
                'vendor_part' => $vendorPart,
                'generic' => $genericPart,
            ];
            $counts['substitute_parts']++;
        }

        // ---------- BOM ----------
        $bomColumns = $parsed['bom']['columns'];
        $genericSubstituteIndex = []; // generic part id => ['vendor_part_id' => VendorPart, 'part' => GciPart]

        foreach ($substitutes as $sub) {
            if ($sub['generic'] === null) {
                continue;
            }
            $genericSubstituteIndex[$sub['generic']->id][] = $sub;
        }

        // Collect parents first so workbook-owned BOMs can be rebuilt cleanly.
        $parentIds = [];
        $parsedBomRows = [];
        foreach ($parsed['bom']['rows'] as $row) {
            $parentNo = $this->partNo($this->cell($bomColumns, 'parent', $row['data'], $row['excel_row']));
            if ($parentNo === null) {
                $warnings[] = "Baris {$row['excel_row']} (BOM): kolom Parent kosong, dilewati.";
                continue;
            }
            $parsedBomRows[] = $row;
            $parentIds[$parentNo] = true;
        }

        // Resolve/create parent parts: FG if listed there, else WIP.
        $parents = [];
        foreach (array_keys($parentIds) as $parentNo) {
            if (isset($fgParts[$parentNo])) {
                $parents[$parentNo] = $fgParts[$parentNo];

                continue;
            }
            $parents[$parentNo] = $this->upsertPart($parentNo, [], 'WIP');
            $counts['wip_parts']++;
        }

        // Rebuild workbook-owned BOM graph deterministically.
        $parentIdsToDelete = array_map(fn ($p) => $p->id, array_values($parents));
        Bom::whereIn('part_id', $parentIdsToDelete)->get()->each(function (Bom $bom) {
            $bom->items()->each(function (BomItem $item) {
                $item->substitutes()->delete();
                $item->delete();
            });
            $bom->delete();
        });

        foreach ($parsedBomRows as $row) {
            $parentNo = $this->partNo($this->cell($bomColumns, 'parent', $row['data'], $row['excel_row']));
            $wipNo = $this->partNo($this->cell($bomColumns, 'wip', $row['data'], $row['excel_row']));
            $childNo = $this->partNo($this->cell($bomColumns, 'child', $row['data'], $row['excel_row']));
            $seq = $this->cell($bomColumns, 'seq', $row['data'], $row['excel_row']);
            $qty = $this->cell($bomColumns, 'qty', $row['data'], $row['excel_row']);
            $uom = $this->normalizeUom($this->cell($bomColumns, 'uom', $row['data'], $row['excel_row']), $warnings, $row['excel_row']);
            $source = $this->cell($bomColumns, 'source', $row['data'], $row['excel_row']);
            $machineName = $this->cell($bomColumns, 'machine', $row['data'], $row['excel_row']);
            $process = $this->cell($bomColumns, 'process', $row['data'], $row['excel_row']);

            if ($childNo === null || $qty === null) {
                $warnings[] = "Baris {$row['excel_row']} (BOM): Child atau Qty kosong, dilewati.";
                continue;
            }

            $parent = $parents[$parentNo];
            $child = $this->resolveChildPart($childNo, $genericParts, $fgParts);

            $bom = $this->bomFor($parent, $counts);
            $machineId = $machineName !== null ? $this->resolveMachine($machineName, $counts) : null;

            $item = BomItem::create([
                'bom_id' => $bom->id,
                'component_part_id' => $child->id,
                'component_part_no' => $child->part_no,
                'usage_qty' => (float) $qty,
                'line_no' => $seq !== null ? (int) $seq : null,
                'consumption_uom' => $uom,
                'make_or_buy' => $this->normalizeSource($source),
                'machine_id' => $machineId,
                'process_name' => $process,
                'wip_part_id' => $wipNo !== null ? GciPart::where('part_no', $wipNo)->value('id') : null,
            ]);
            $counts['bom_items']++;

            // Link supplier substitutes for generic material lines.
            $subList = $genericSubstituteIndex[$child->id] ?? [];
            if ($subList !== []) {
                $item->incoming_part_id = $subList[0]['vendor_part']->id;
                $item->save();
                $counts['substitutes_linked']++;

                $priority = 1;
                foreach ($subList as $sub) {
                    BomItemSubstitute::create([
                        'bom_item_id' => $item->id,
                        'substitute_part_id' => $sub['part']->id,
                        'substitute_part_no' => $sub['part']->part_no,
                        'incoming_part_id' => $sub['vendor_part']->id,
                        'ratio' => 1,
                        'priority' => $priority++,
                        'status' => 'active',
                    ]);
                }
            } else {
                // Child is itself a supplier-specific part (or plain part).
                $vendorPartId = VendorPart::where('gci_part_id', $child->id)->value('id');
                if ($vendorPartId !== null) {
                    $item->incoming_part_id = $vendorPartId;
                    $item->save();
                }
            }
        }

        return $counts;
    }

    private function partNo(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = strtoupper(preg_replace('/\s+/', ' ', trim($value)));

        return $value === '' ? null : $value;
    }

    private function nameKey(?string $value): string
    {
        return $this->normalizeKey($value);
    }

    private function upsertPart(string $partNo, array $attributes, string $classification): GciPart
    {
        $part = GciPart::where('part_no', $partNo)->first();

        $payload = array_filter([
            'part_name' => $attributes['part_name'] ?? null,
            'model' => $attributes['model'] ?? null,
            'size' => $attributes['size'] ?? null,
            'uom' => $attributes['uom'] ?? null,
        ], fn ($v) => $v !== null);

        if ($part === null) {
            $part = GciPart::create(array_merge($payload, [
                'part_no' => $partNo,
                'classification' => $classification,
                'status' => 'active',
            ]));

            return $part;
        }

        // Never downgrade an existing classification (e.g. FG already known).
        $payload['classification'] = $part->classification === $classification
            ? $classification
            : $this->strongerClassification($part->classification, $classification);
        $payload['status'] = $part->status ?: 'active';
        $part->fill($payload);
        $part->save();

        return $part;
    }

    private function strongerClassification(?string $current, string $incoming): string
    {
        $rank = ['FG' => 3, 'WIP' => 2, 'RM' => 1];
        $currentRank = $rank[$current] ?? 0;
        $incomingRank = $rank[$incoming] ?? 0;

        return $incomingRank > $currentRank ? $incoming : (string) $current;
    }

    private function resolveChildPart(string $childNo, array $genericParts, array $fgParts): GciPart
    {
        if (isset($genericParts[$childNo])) {
            return $genericParts[$childNo];
        }

        return $this->upsertPart($childNo, [], 'RM');
    }

    private function resolveVendor(string $supplierName, array &$vendorsByAlias, array &$counts): Vendor
    {
        $alias = $this->nameKey($supplierName);

        if (isset($vendorsByAlias[$alias])) {
            return $vendorsByAlias[$alias];
        }

        // Match existing vendors punctuation/case-insensitively.
        foreach (Vendor::all() as $vendor) {
            if ($this->nameKey($vendor->vendor_name) === $alias) {
                $vendorsByAlias[$alias] = $vendor;

                return $vendor;
            }
        }

        $vendor = Vendor::create([
            'vendor_name' => $supplierName,
            'vendor_code' => 'SUP-' . strtoupper(substr(md5($alias), 0, 8)),
            'vendor_type' => 'import',
            'status' => 'active',
        ]);
        $vendorsByAlias[$alias] = $vendor;
        $counts['vendors_created']++;

        return $vendor;
    }

    private function resolveMachine(string $machineName, array &$counts): ?int
    {
        $name = trim($machineName);
        $code = 'MC-' . strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $name));
        $code = substr($code, 0, 50);
        $code = rtrim($code, '-');

        $machine = Machine::where('code', $code)->first();
        if ($machine === null) {
            $machine = Machine::create([
                'code' => $code,
                'name' => $name,
                'is_active' => true,
            ]);
            $counts['machines_created']++;
        }

        return $machine->id;
    }

    private function normalizeSource(?string $source): ?string
    {
        if ($source === null) {
            return null;
        }
        $upper = strtoupper(trim($source));
        if (in_array($upper, ['MAKE', 'BUY', 'FREE ISSUE', 'FREE_ISSUE'], true)) {
            return str_replace(' ', '_', $upper);
        }

        return $upper;
    }

    private function bomFor(GciPart $parent, array &$counts): Bom
    {
        $bom = Bom::where('part_id', $parent->id)->first();
        if ($bom === null) {
            $bom = Bom::create([
                'part_id' => $parent->id,
                'revision' => 'A',
                'effective_date' => now()->toDateString(),
                'status' => 'active',
            ]);
            $counts['boms']++;
        }

        return $bom;
    }
}
