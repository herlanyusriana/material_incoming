<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\GciPartVendor;
use App\Models\Machine;
use App\Models\MaterialSubstitute;
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

    /**
     * Reconciliation for BOM rows whose Child Part No. formula evaluates to
     * #N/A in the workbook. Keyed by normalized Child Part Name.
     * (Approved by data owner — see master mtrl: PIN HINGE LOWER / BRACKET HINGE LOWER.)
     */
    private const CHILD_NAME_FALLBACK = [
        'pinhollow' => 'PINHLKG1533C',
        'brackethl' => 'BRKG2057C',
    ];

    /** @var array<string, string[]> accepted normalized header aliases per logical field */
    private const HEADER_ALIASES = [
        'part_no' => ['partno', 'partnumber', 'part', 'kodepart', 'mtrlpartno', 'materialno', 'subspart', 'subspartno', 'subspartnumber', 'substitutepartno', 'substitutepart'],
        'part_name' => ['partname', 'partdescription', 'description', 'name', 'nama', 'namapart', 'namamaterial', 'namabarang'],
        'model' => ['model', 'tipe', 'typepart'],
        'uom' => ['uom', 'unit', 'satuan', 'uomcode', 'unitofmeasure', 'uomrm'],
        'size' => ['size', 'ukuran', 'dimension', 'dimensi', 'spec', 'spesifikasi'],
        'supplier' => ['supplier', 'suplier', 'vendor', 'namasupplier', 'namavendor', 'suppliername', 'vendorname'],
        'supplier_part_no' => ['supplierpartno', 'supplierpartnumber', 'vendorpartno', 'kodepartsupplier', 'partnosupplier', 'partnovendor'],
        'generic_part_no' => ['genericpartno', 'genericpart', 'generic', 'basematerialpartno', 'mastermtrlno', 'materialpartno', 'mtrlno', 'materialpart', 'materialpartnumber'],
        'fg_part_no' => ['fgpartno', 'fgpart', 'fgpartnumber', 'fgno'],
        'parent' => ['parent', 'parentpart', 'parentpartno', 'parentassembly', 'assembly', 'assemblypartno', 'output'],
        'parent_name' => ['parentpartname', 'parentname', 'wippartname', 'wipname'],
        'parent_qty' => ['parentpartqty', 'parentqty', 'wipqty', 'wipquantity'],
        'parent_uom' => ['parentpartuom', 'parentuom', 'wipuom'],
        'wip' => ['wip', 'wippart', 'wippartno', 'wipno', 'wipoutput', 'intermediate'],
        'child' => ['child', 'childpart', 'childpartno', 'component', 'componentpart', 'componentpartno', 'materialpart', 'materialpartno'],
        'child_name' => ['childpartname', 'childname', 'componentpartname', 'componentname'],
        'seq' => ['seq', 'sequence', 'step', 'line', 'lineno', 'linenumber', 'urutan', 'nostep'],
        'qty' => ['qty', 'quantity', 'usage', 'usageqty', 'consumption', 'consumptionqty', 'netrequired', 'childpartqty', 'childqty'],
        'source' => ['source', 'sourcetype', 'makeorbuy', 'makebuy', 'sourcecode'],
        'machine' => ['machine', 'machinename', 'mesin', 'namamesin'],
        'process' => ['process', 'processname', 'proses', 'operation', 'operasi'],
        'special' => ['spesial', 'special'],
    ];

    /** @var array<string, int> normalized header key -> column index per sheet */
    private array $columnMap = [];

    public function import(string $path, bool $dryRun = false, bool $replaceParts = false): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException("Workbook tidak ditemukan atau tidak bisa dibaca: {$path}");
        }

        $parsed = $this->parse($path);

        DB::beginTransaction();

        try {
            if ($replaceParts) {
                $this->clearPartMaster();
            }
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

    /**
     * Remove the workbook-owned part graph before a full source-of-truth import.
     * The surrounding transaction guarantees a complete rollback when another
     * application table still references a part.
     */
    private function clearPartMaster(): void
    {
        try {
            BomItem::query()->delete();
            Bom::query()->delete();
            MaterialSubstitute::query()->delete();
            GciPartVendor::query()->delete();
            VendorPart::withTrashed()->forceDelete();
            GciPart::withTrashed()->forceDelete();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Reset Part Master dibatalkan karena masih ada data operasional yang mereferensikan part. '
                . 'Tidak ada data yang diubah. Detail: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    // ------------------------------------------------------------------
    // Parsing
    // ------------------------------------------------------------------

    private function parse(string $path): array
    {
        $reader = IOFactory::createReader(IOFactory::READER_XLSX);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);

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
        // Setiap baris: material generic (D) + substitusi supplier (E) + vendor (H).
        // Vendor alias registry: normalized alias -> Vendor model.
        $vendorsByAlias = [];

        $mtrlColumns = $parsed['mtrl']['columns'];
        $genericParts = [];   // part_no => GciPart (classification RM)
        $genericByKey = [];   // normalized name => GciPart
        $substitutes = [];    // list of ['part' => GciPart, 'vendor' => Vendor, 'vendor_part' => VendorPart, 'generic' => GciPart]
        $subsByPartNo = [];   // substitute part_no => generic part_no

        foreach ($parsed['mtrl']['rows'] as $row) {
            $genericNo = $this->partNo($this->cell($mtrlColumns, 'generic_part_no', $row['data'], $row['excel_row']));
            $subsNo = $this->partNo($this->cell($mtrlColumns, 'part_no', $row['data'], $row['excel_row']));
            $partName = $this->cell($mtrlColumns, 'part_name', $row['data'], $row['excel_row']);
            $supplier = $this->cell($mtrlColumns, 'supplier', $row['data'], $row['excel_row']);
            $uom = $this->normalizeUom($this->cell($mtrlColumns, 'uom', $row['data'], $row['excel_row']), $warnings, $row['excel_row']);
            $size = $this->cell($mtrlColumns, 'size', $row['data'], $row['excel_row']);
            $source = $this->cell($mtrlColumns, 'source', $row['data'], $row['excel_row']);

            if ($genericNo === null && $subsNo === null) {
                $warnings[] = "Baris {$row['excel_row']} (master mtrl): Material Part # dan Subs Part # kosong, dilewati.";
                continue;
            }

            // Generic material — wajib ada; kalau baris hanya generic tanpa subs pun tetap dibuat.
            if ($genericNo !== null && !isset($genericParts[$genericNo])) {
                $genericParts[$genericNo] = $this->upsertPart($genericNo, [
                    'part_name' => $partName,
                    'size' => $size,
                    'uom' => $uom,
                ], 'RM');
                $genericByKey[$this->nameKey($partName)] = $genericParts[$genericNo];
                $counts['generic_rm_parts']++;
            }

            // Baris generic murni tanpa kolom Generic Part # (workbook sintetis/lama):
            // Part No adalah material generic itu sendiri.
            if ($genericNo === null && $supplier === null && $subsNo !== null) {
                if (!isset($genericParts[$subsNo])) {
                    $genericParts[$subsNo] = $this->upsertPart($subsNo, [
                        'part_name' => $partName,
                        'size' => $size,
                        'uom' => $uom,
                    ], 'RM');
                    $genericByKey[$this->nameKey($partName)] = $genericParts[$subsNo];
                    $counts['generic_rm_parts']++;
                }

                continue;
            }

            // Baris generic-only (tanpa supplier): hanya master material.
            if ($subsNo === null || strtolower((string) $subsNo) === strtolower((string) $genericNo)) {
                if ($subsNo !== null && $supplier !== null) {
                    // Vendor memasok material generic langsung (subs = generic).
                    $vendor = $this->resolveVendor($supplier, $vendorsByAlias, $counts);
                    $this->upsertVendorLinks($genericParts[$genericNo], $vendor, $subsNo, $partName, $uom);
                }
                continue;
            }

            if ($supplier === null) {
                $warnings[] = "Baris {$row['excel_row']} (master mtrl): Subs Part # '{$subsNo}' tanpa Supplier, substitusi dilewati.";
                continue;
            }

            $vendor = $this->resolveVendor($supplier, $vendorsByAlias, $counts);
            $vendorType = $this->normalizeVendorType($source);
            if ($vendorType !== null && $vendor->vendor_type !== $vendorType) {
                $vendor->vendor_type = $vendorType;
                $vendor->save();
            }

            $part = $this->upsertPart($subsNo, ['part_name' => $partName, 'size' => $size, 'uom' => $uom], 'RM');
            $vendorPart = $this->upsertVendorLinks($part, $vendor, $subsNo, $partName, $uom);

            $genericPart = $genericParts[$genericNo] ?? GciPart::where('part_no', $genericNo)->first();
            if ($genericPart === null) {
                $warnings[] = "Baris {$row['excel_row']} (master mtrl): substitusi '{$subsNo}' tidak menemukan material generic ({$genericNo}), tidak ditautkan ke BOM.";
            }

            $substitutes[] = [
                'part' => $part,
                'vendor' => $vendor,
                'vendor_part' => $vendorPart,
                'generic' => $genericPart,
            ];
            $subsByPartNo[$subsNo] = $genericNo;
            $counts['substitute_parts']++;
        }

        // ---------- BOM ----------
        $bomColumns = $parsed['bom']['columns'];

        // Persist global substitutes from master mtrl into material_substitutes.
        // Deduplicate by (generic, sub, vendor).
        $seen = [];
        foreach ($substitutes as $sub) {
            if ($sub['generic'] === null) {
                continue;
            }
            $key = "{$sub['generic']->id}:{$sub['part']->id}:{$sub['vendor_part']->id}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            MaterialSubstitute::updateOrCreate(
                [
                    'generic_part_id' => $sub['generic']->id,
                    'substitute_part_id' => $sub['part']->id,
                    'vendor_part_id' => $sub['vendor_part']->id,
                ],
                [
                    'ratio' => 1,
                    'priority' => $sub['vendor']->id,
                    'status' => 'active',
                ]
            );
            $counts['substitutes_linked']++;
        }

        // Kumpulkan owner (FG) + parent (WIP) + baris valid lebih dulu.
        $parsedBomRows = [];
        $ownerNos = [];
        $parentNos = [];
        $parentMetadata = [];
        foreach ($parsed['bom']['rows'] as $row) {
            $ownerNo = $this->partNo($this->cell($bomColumns, 'fg_part_no', $row['data'], $row['excel_row']));
            $parentNo = $this->partNo($this->cell($bomColumns, 'parent', $row['data'], $row['excel_row']));
            $childNo = $this->partNo($this->cell($bomColumns, 'child', $row['data'], $row['excel_row']));
            $qty = $this->cell($bomColumns, 'qty', $row['data'], $row['excel_row']);

            if ($childNo === null || $qty === null) {
                // Child Part No. bisa #N/A karena formula XLOOKUP gagal resolve.
                // Coba recover dari Child Part Name (keputusan data owner).
                $childNameForFallback = $this->cell($bomColumns, 'child_name', $row['data'], $row['excel_row']);
                $fallbackKey = $this->nameKey($childNameForFallback);
                $fallbackNo = self::CHILD_NAME_FALLBACK[$fallbackKey] ?? null;
                if ($childNo === null && $fallbackNo !== null) {
                    $childNo = $fallbackNo;
                    $warnings[] = "Baris {$row['excel_row']} (BOM): Child Part No. #N/A → pakai '{$fallbackNo}' (dari nama '{$childNameForFallback}').";
                } else {
                    $reason = $childNo === null ? 'Child Part No. tidak valid/kosong (mis. #N/A)' : 'Child Qty kosong';
                    $warnings[] = "Baris {$row['excel_row']} (BOM): {$reason}, dilewati.";
                    continue;
                }
            }

            $effectiveOwner = $ownerNo ?? $parentNo;
            if ($effectiveOwner === null) {
                $warnings[] = "Baris {$row['excel_row']} (BOM): FG Part No. dan Parent kosong, dilewati.";
                continue;
            }

            $parsedBomRows[] = $row;
            $ownerNos[$effectiveOwner] = true;
            if ($parentNo !== null) {
                $parentNos[$parentNo] = true;
                $parentMetadata[$parentNo] = array_filter([
                    'part_name' => $this->cell($bomColumns, 'parent_name', $row['data'], $row['excel_row']),
                    'uom' => $this->normalizeUom(
                        $this->cell($bomColumns, 'parent_uom', $row['data'], $row['excel_row']),
                        $warnings,
                        $row['excel_row']
                    ),
                ], fn ($value) => $value !== null);
            }
        }

        // Owner: FG kalau terdaftar di master FG, kalau tidak → WIP.
        $owners = [];
        foreach (array_keys($ownerNos) as $ownerNo) {
            if (isset($fgParts[$ownerNo])) {
                $owners[$ownerNo] = $fgParts[$ownerNo];
                continue;
            }
            $owners[$ownerNo] = $this->upsertPart($ownerNo, [], 'WIP');
            $counts['wip_parts']++;
        }

        // Parent = WIP/output part dari tiap line.
        $parents = [];
        foreach (array_keys($parentNos) as $parentNo) {
            if (isset($owners[$parentNo])) {
                $parents[$parentNo] = $this->upsertPart(
                    $parentNo,
                    $parentMetadata[$parentNo] ?? [],
                    'WIP'
                );
                continue;
            }
            $parents[$parentNo] = $this->upsertPart($parentNo, $parentMetadata[$parentNo] ?? [], 'WIP');
            $counts['wip_parts']++;
        }

        // Rebuild workbook-owned BOM graph deterministically.
        $ownedIds = array_values(array_unique(array_merge(
            array_map(fn ($p) => $p->id, $owners),
            array_map(fn ($p) => $p->id, $parents)
        )));
        Bom::whereIn('part_id', $ownedIds)->get()->each(function (Bom $bom) {
            $bom->items()->each(function (BomItem $item) {
                $item->delete();
            });
            $bom->delete();
        });

        // Build a quick lookup: substitute_part_id → vendor_part_id from material_substitutes.
        $subVendorMap = [];
        foreach ($substitutes as $sub) {
            if ($sub['generic'] !== null && $sub['part'] !== null && $sub['vendor_part'] !== null) {
                $subVendorMap[$sub['part']->id] = $sub['vendor_part']->id;
            }
        }

        foreach ($parsedBomRows as $row) {
            $ownerNo = $this->partNo($this->cell($bomColumns, 'fg_part_no', $row['data'], $row['excel_row']))
                ?? $this->partNo($this->cell($bomColumns, 'parent', $row['data'], $row['excel_row']));
            $parentNo = $this->partNo($this->cell($bomColumns, 'parent', $row['data'], $row['excel_row']));
            $childNo = $this->partNo($this->cell($bomColumns, 'child', $row['data'], $row['excel_row']));
            $childName = $this->cell($bomColumns, 'child_name', $row['data'], $row['excel_row']);

            // Reapply child-name fallback for #N/A child numbers (same as first pass).
            if ($childNo === null) {
                $childNo = self::CHILD_NAME_FALLBACK[$this->nameKey($childName)] ?? null;
            }

            $seq = $this->cell($bomColumns, 'seq', $row['data'], $row['excel_row']);
            $qty = $this->cell($bomColumns, 'qty', $row['data'], $row['excel_row']);
            $uom = $this->normalizeUom($this->cell($bomColumns, 'uom', $row['data'], $row['excel_row']), $warnings, $row['excel_row']);
            $source = $this->cell($bomColumns, 'source', $row['data'], $row['excel_row']);
            $machineName = $this->cell($bomColumns, 'machine', $row['data'], $row['excel_row']);
            $process = $this->cell($bomColumns, 'process', $row['data'], $row['excel_row']);
            $special = $this->cell($bomColumns, 'special', $row['data'], $row['excel_row']);
            $wipPartName = $this->cell($bomColumns, 'parent_name', $row['data'], $row['excel_row']);
            $wipQty = $this->cell($bomColumns, 'parent_qty', $row['data'], $row['excel_row']);
            $wipUom = $this->normalizeUom(
                $this->cell($bomColumns, 'parent_uom', $row['data'], $row['excel_row']),
                $warnings,
                $row['excel_row']
            );

            $owner = $owners[$ownerNo];
            $child = $this->resolveChildPart($childNo, $genericParts, [
                'part_name' => $childName,
                'uom' => $uom,
            ]);

            // WIP/output part
            $wipNo = $this->partNo($this->cell($bomColumns, 'wip', $row['data'], $row['excel_row']));
            $hasExplicitFgOwner = $this->cell($bomColumns, 'fg_part_no', $row['data'], $row['excel_row']) !== null;
            if ($wipNo === null && $hasExplicitFgOwner && $parentNo !== null) {
                $wipNo = $parentNo;
            }
            $wipPartId = $wipNo !== null ? GciPart::where('part_no', $wipNo)->value('id') : null;

            $bom = $this->bomFor($owner, $counts);
            // Machine: pakai Machine Name; bila kosong, fallback ke Process Name (keputusan user).
            $machineRef = ($machineName !== null && $machineName !== '')
                ? $machineName
                : (($process !== null && $process !== '') ? $process : null);
            $machineId = $machineRef !== null ? $this->resolveMachine($machineRef, $counts) : null;

            // Resolve incoming_part_id: look up vendor_part for this child.
            // Priority: child's own vendor link → substitute's vendor link.
            $incomingPartId = VendorPart::where('gci_part_id', $child->id)->value('id')
                ?? $subVendorMap[$child->id] ?? null;

            // If child is a generic RM, also pick from the first global substitute vendor.
            if ($incomingPartId === null && isset($genericParts[$childNo])) {
                // This child IS a generic RM — pick first substitute's vendor_part.
                $firstSub = MaterialSubstitute::where('generic_part_id', $child->id)
                    ->orderBy('priority')
                    ->value('vendor_part_id');
                $incomingPartId = $firstSub;
            }

            // If child is a substitute (not generic), look up its generic's first substitute vendor.
            if ($incomingPartId === null) {
                $genericForSub = $subsByPartNo[$childNo] ?? null;
                if ($genericForSub !== null) {
                    $genericId = GciPart::where('part_no', $genericForSub)->value('id');
                    if ($genericId !== null) {
                        $incomingPartId = MaterialSubstitute::where('generic_part_id', $genericId)
                            ->orderBy('priority')
                            ->value('vendor_part_id');
                    }
                }
            }

            // Material descriptor: isi dari Excel (Child Part Name + Size master mtrl).
            // material_spec memang tidak ada sumbernya di sheet mana pun → dibiarkan null.
            $materialName = $childName !== null && $childName !== ''
                ? $childName
                : ($child->part_name ?? $child->part_no);

            BomItem::create([
                'bom_id' => $bom->id,
                'component_part_id' => $child->id,
                'component_part_no' => $child->part_no,
                'incoming_part_id' => $incomingPartId,
                'usage_qty' => (float) $qty,
                'line_no' => $seq !== null ? (int) $seq : null,
                'consumption_uom' => $uom,
                'make_or_buy' => $this->normalizeSource($source),
                'machine_id' => $machineId,
                'process_name' => $process,
                'special' => $special,
                'wip_part_id' => $wipPartId,
                'wip_part_no' => $wipNo,
                'wip_part_name' => $wipPartName,
                'wip_qty' => $wipQty !== null ? (float) $wipQty : null,
                'wip_uom' => $wipUom,
                'material_name' => $materialName,
                'material_size' => $child->size ?? null,
                'material_spec' => null,
            ]);
            $counts['bom_items']++;
        }

        return $counts;
    }

    private function partNo(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = strtoupper(preg_replace('/\s+/', ' ', trim($value)));
        if ($value === '' || preg_match('/^#N\/?A/i', $value) || $value === '#REF!' || $value === '#VALUE!') {
            return null;
        }

        return $value;
    }

    /** Bridge tabel vendor: vendor_parts + gci_part_vendor (MOQ/lead time). */
    private function upsertVendorLinks(GciPart $part, Vendor $vendor, string $vendorPartNo, ?string $vendorPartName, ?string $uom): VendorPart
    {
        $vendorPart = VendorPart::updateOrCreate(
            ['gci_part_id' => $part->id, 'vendor_id' => $vendor->id],
            [
                'vendor_part_no' => $vendorPartNo,
                'vendor_part_name' => $vendorPartName,
                'uom' => $uom,
                'status' => 'active',
            ]
        );

        GciPartVendor::updateOrCreate(
            ['gci_part_id' => $part->id, 'vendor_id' => $vendor->id],
            [
                'vendor_part_no' => $vendorPartNo,
                'vendor_part_name' => $vendorPartName,
                'uom' => $uom,
                'status' => 'active',
            ]
        );

        return $vendorPart;
    }

    private function normalizeVendorType(?string $source): ?string
    {
        if ($source === null) {
            return null;
        }
        $lower = strtolower(trim($source));

        return match ($lower) {
            'import' => 'import',
            'local' => 'local',
            default => null,
        };
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

    private function resolveChildPart(string $childNo, array $genericParts, array $attributes = []): GciPart
    {
        if (isset($genericParts[$childNo])) {
            return $this->upsertPart($childNo, $attributes, 'RM');
        }

        return $this->upsertPart($childNo, $attributes, 'RM');
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

        // Real workbook: Source = Vendor / Prod / Subcon / BUY / MAKE / FREE_ISSUE.
        return match ($upper) {
            'VENDOR', 'VENDOR PART', 'BUY' => 'BUY',
            'PROD', 'PRODUCTION', 'MAKE' => 'MAKE',
            'SUBCON', 'SUBCONTRACT' => 'SUBCON',
            'FREE ISSUE', 'FREE_ISSUE' => 'FREE_ISSUE',
            default => $upper,
        };
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
