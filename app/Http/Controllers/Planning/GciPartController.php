<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Exports\GciPartsExport;
use App\Imports\GciPartsImport;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\BomItemSubstitute;
use App\Models\GciPart;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class GciPartController extends Controller
{
    private const CONSUMPTION_POLICIES = [
        'direct_issue',
        'backflush_return',
        'backflush_line_stock',
    ];

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $classification = $request->query('classification');
        $limit = (int) $request->query('limit', 20);

        if (mb_strlen($q) < 2 && !$request->has('all')) {
            return response()->json([]);
        }

        $query = GciPart::query()
            ->select(['id', 'part_no', 'part_name', 'model', 'classification'])
            ->when($classification, function ($qr) use ($classification) {
                if (is_array($classification)) {
                    $qr->whereIn('classification', $classification);
                } else {
                    $qr->where('classification', $classification);
                }
            })
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($inner) use ($q) {
                    $inner->where('part_no', 'like', '%' . $q . '%')
                        ->orWhere('part_name', 'like', '%' . $q . '%')
                        ->orWhere('model', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('part_no')
            ->limit($limit);

        return response()->json($query->get());
    }

    public function getBomInfo(GciPart $gciPart)
    {
        $bom = \App\Models\Bom::where('part_id', $gciPart->id)->latest()->first();

        if (!$bom) {
            return response()->json(['success' => false, 'message' => 'No BOM found']);
        }

        // Try to get from first WIP item, otherwise from first item
        $bomItems = $bom->items()->orderBy('line_no')->get();
        $targetItem = $bomItems->firstWhere('wip_part_id', '!=', null) ?? $bomItems->first();

        if (!$targetItem) {
            return response()->json(['success' => false, 'message' => 'No BOM items found']);
        }

        return response()->json([
            'success' => true,
            'bom' => [
                'process_name' => $targetItem->process_name,
                'machine_id' => $targetItem->machine_id,
            ]
        ]);
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        // Get classification from route default or query param
        $classification = $request->route('classification') ?? $request->query('classification');

        $qParam = trim((string) $request->query('q', ''));

        $parts = GciPart::query()
            ->with('customers')
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($classification, fn($q) => $q->where('classification', strtoupper($classification)))
            ->when($qParam, function ($query) use ($qParam) {
                $query->where(function ($sub) use ($qParam) {
                    $sub->where('part_no', 'like', "%{$qParam}%")
                        ->orWhere('part_name', 'like', "%{$qParam}%")
                        ->orWhere('model', 'like', "%{$qParam}%");
                });
            })
            ->orderBy('part_no')
            ->paginate(25)
            ->withQueryString();

        $customers = \App\Models\Customer::where('status', 'active')->orderBy('name')->get();

        // FG parts that have a BOM (for RM destination field)
        $fgPartsWithBom = GciPart::where('classification', 'FG')
            ->whereHas('bom')
            ->orderBy('part_no')
            ->get(['id', 'part_no', 'part_name']);

        // RM → linked FG IDs mapping (for edit pre-populate)
        // Load untuk semua RM parts di halaman (bukan cuma saat filter RM)
        $rmFgMap = [];
        $rmIds = $parts->filter(fn($p) => $p->classification === 'RM')->pluck('id')->toArray();
        if (!empty($rmIds)) {
            $links = BomItem::whereIn('component_part_id', $rmIds)
                ->whereHas('bom')
                ->with('bom:id,part_id')
                ->get(['id', 'bom_id', 'component_part_id']);
            foreach ($links as $link) {
                $rmFgMap[$link->component_part_id][] = $link->bom->part_id;
            }
        }

        $vendors = Vendor::where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']);

        // Part → linked Vendor IDs mapping (for edit pre-populate)
        $partVendorMap = [];
        $partIds = $parts->pluck('id')->toArray();
        if (!empty($partIds)) {
            $vendorLinks = DB::table('gci_part_vendor')
                ->whereIn('gci_part_id', $partIds)
                ->get(['gci_part_id', 'vendor_id']);
            foreach ($vendorLinks as $vl) {
                $partVendorMap[$vl->gci_part_id][] = $vl->vendor_id;
            }
        }

        // Substitutes FOR this part (when used as component in BOMs)
        $partSubstitutesMap = [];
        if (!empty($rmIds)) {
            $subsForParts = BomItemSubstitute::query()
                ->whereHas('bomItem', fn($q) => $q->whereIn('component_part_id', $rmIds))
                ->with(['bomItem.bom.part:id,part_no,part_name', 'bomItem:id,bom_id,component_part_id', 'part:id,part_no,part_name'])
                ->get();

            foreach ($subsForParts as $sub) {
                $componentPartId = $sub->bomItem->component_part_id;
                $partSubstitutesMap[$componentPartId][] = [
                    'id' => $sub->id,
                    'bom_item_id' => $sub->bom_item_id,
                    'fg_part_id' => $sub->bomItem->bom->part->id ?? null,
                    'fg_part_no' => $sub->bomItem->bom->part->part_no ?? '?',
                    'substitute_part_id' => $sub->substitute_part_id,
                    'substitute_part_no' => $sub->part->part_no ?? $sub->substitute_part_no,
                    'substitute_part_name' => $sub->part->part_name ?? '',
                    'ratio' => $sub->ratio,
                    'priority' => $sub->priority,
                    'status' => $sub->status,
                    'notes' => $sub->notes,
                ];
            }
        }

        // Where this part IS a substitute for other parts
        $partAsSubstituteMap = [];
        if (!empty($rmIds)) {
            $asSubstitute = BomItemSubstitute::query()
                ->whereIn('substitute_part_id', $rmIds)
                ->with(['bomItem.bom.part:id,part_no', 'bomItem:id,bom_id,component_part_id,component_part_no', 'bomItem.componentPart:id,part_no,part_name'])
                ->get();

            foreach ($asSubstitute as $sub) {
                $partAsSubstituteMap[$sub->substitute_part_id][] = [
                    'id' => $sub->id,
                    'fg_part_no' => $sub->bomItem->bom->part->part_no ?? '?',
                    'original_rm_part_no' => $sub->bomItem->componentPart->part_no ?? $sub->bomItem->component_part_no,
                    'original_rm_part_name' => $sub->bomItem->componentPart->part_name ?? '',
                    'ratio' => $sub->ratio,
                    'priority' => $sub->priority,
                    'status' => $sub->status,
                ];
            }
        }

        // All RM parts for substitute dropdown
        $rmParts = GciPart::where('classification', 'RM')
            ->where('status', 'active')
            ->orderBy('part_no')
            ->get(['id', 'part_no', 'part_name']);

        // Counts per classification untuk tab badges
        $classCounts = GciPart::query()
            ->selectRaw("classification, count(*) as total")
            ->groupBy('classification')
            ->pluck('total', 'classification')
            ->toArray();

        return view('planning.gci_parts.index', compact('parts', 'status', 'classification', 'customers', 'qParam', 'fgPartsWithBom', 'rmFgMap', 'vendors', 'partVendorMap', 'partSubstitutesMap', 'partAsSubstituteMap', 'rmParts', 'classCounts'));
    }

    public function export()
    {
        return Excel::download(new GciPartsExport(), 'gci_parts_' . date('Y-m-d_His') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ]);

        try {
            $import = new GciPartsImport();

            DB::transaction(function () use ($import, $request) {
                Excel::import($import, $request->file('file'));
            });

            $parts = [];
            if ($import->createdCount > 0) {
                $parts[] = "{$import->createdCount} created";
            }
            if ($import->updatedCount > 0) {
                $parts[] = "{$import->updatedCount} updated";
            }
            $msg = 'Part GCI imported successfully. ' . implode(', ', $parts) . '.';

            if ($import->substituteCount > 0) {
                $msg .= " {$import->substituteCount} substitutes processed.";
            }

            $missingComp = array_keys($import->missingComponentParts);
            $missingSub = array_keys($import->missingSubstituteParts);
            if (!empty($missingComp) || !empty($missingSub)) {
                $allMissing = array_unique(array_merge($missingComp, $missingSub));
                $preview = implode(', ', array_slice($allMissing, 0, 10));
                $more = count($allMissing) > 10 ? (' … +' . (count($allMissing) - 10) . ' more') : '';
                $msg .= " Missing parts for substitutes: {$preview}{$more}.";
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['exists:customers,id'],
            'part_no' => ['required', 'string', 'max:100'],
            'classification' => ['required', Rule::in(['FG', 'WIP', 'RM'])],
            'part_name' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:255'],
            'is_backflush' => ['nullable', 'boolean'],
            'consumption_policy' => ['nullable', Rule::in(self::CONSUMPTION_POLICIES)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'destination_fg_ids' => ['nullable', 'array'],
            'destination_fg_ids.*' => ['exists:gci_parts,id'],
            'vendor_ids' => ['nullable', 'array'],
            'vendor_ids.*' => ['exists:vendors,id'],
        ]);

        $destinationFgIds = $validated['destination_fg_ids'] ?? [];
        $vendorIds = $validated['vendor_ids'] ?? [];
        unset($validated['destination_fg_ids'], $validated['vendor_ids']);

        $validated['part_no'] = strtoupper(trim($validated['part_no']));
        $validated['classification'] = strtoupper(trim($validated['classification']));
        $validated['part_name'] = $validated['part_name'] ? trim($validated['part_name']) : null;
        $validated['size'] = $validated['size'] ? trim($validated['size']) : null;
        $validated['model'] = $validated['model'] ? trim($validated['model']) : null;
        $validated['consumption_policy'] = $validated['consumption_policy']
            ?? ($request->boolean('is_backflush', true) ? 'backflush_return' : 'direct_issue');
        $validated['is_backflush'] = $validated['consumption_policy'] !== 'direct_issue';
        $validated['policy_confirmed_at'] = now();
        $validated['policy_confirmed_by'] = auth()->id();

        $customerIds = $request->input('customer_ids', []);

        // Customer assignment only applies to FG. RM/WIP stay internal.
        if (in_array($validated['classification'], ['RM', 'WIP'], true)) {
            $validated['model'] = null;
            $customerIds = [];
        }

        if (!$request->boolean('confirm_duplicate')) {
            if (GciPart::where('part_no', $validated['part_no'])->exists()) {
                return back()
                    ->withInput()
                    ->with('duplicate_warning_data', $request->all())
                    ->with('error', "Part number '{$validated['part_no']}' already exists. Please confirm to proceed.");
            }
        }

        $gciPart = GciPart::create($validated);

        if (!empty($customerIds)) {
            $gciPart->customers()->sync($customerIds);
        }

        // Auto-link RM to FG BOMs
        $bomLinked = 0;
        if ($validated['classification'] === 'RM' && !empty($destinationFgIds)) {
            foreach ($destinationFgIds as $fgId) {
                $bom = Bom::where('part_id', $fgId)->first();
                if ($bom) {
                    $nextLine = ($bom->items()->max('line_no') ?? 0) + 1;
                    BomItem::create([
                        'bom_id' => $bom->id,
                        'component_part_id' => $gciPart->id,
                        'component_part_no' => $gciPart->part_no,
                        'line_no' => $nextLine,
                        'usage_qty' => 1,
                        'make_or_buy' => 'buy',
                    ]);
                    $bomLinked++;
                }
            }
        }

        // Assign vendors for RM
        if ($validated['classification'] === 'RM' && !empty($vendorIds)) {
            $gciPart->vendors()->syncWithoutDetaching($vendorIds);
        }

        $msg = 'Part GCI created.';
        if ($bomLinked > 0) {
            $msg .= " Linked to {$bomLinked} FG BOM(s).";
        }

        return back()->with('success', $msg);
    }

    public function update(Request $request, GciPart $gciPart)
    {
        $validated = $request->validate([
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['exists:customers,id'],
            'part_no' => ['required', 'string', 'max:100'],
            'classification' => ['required', Rule::in(['FG', 'WIP', 'RM'])],
            'part_name' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:255'],
            'is_backflush' => ['nullable', 'boolean'],
            'consumption_policy' => ['nullable', Rule::in(self::CONSUMPTION_POLICIES)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'destination_fg_ids' => ['nullable', 'array'],
            'destination_fg_ids.*' => ['exists:gci_parts,id'],
            'vendor_ids' => ['nullable', 'array'],
            'vendor_ids.*' => ['exists:vendors,id'],
        ]);

        $destinationFgIds = $validated['destination_fg_ids'] ?? [];
        $vendorIds = $validated['vendor_ids'] ?? [];
        unset($validated['destination_fg_ids'], $validated['vendor_ids']);

        $validated['part_no'] = strtoupper(trim($validated['part_no']));
        $validated['classification'] = strtoupper(trim($validated['classification']));
        $validated['part_name'] = $validated['part_name'] ? trim($validated['part_name']) : null;
        $validated['size'] = $validated['size'] ? trim($validated['size']) : null;
        $validated['model'] = $validated['model'] ? trim($validated['model']) : null;
        $validated['consumption_policy'] = $validated['consumption_policy']
            ?? ($request->boolean('is_backflush', true) ? 'backflush_return' : 'direct_issue');
        $validated['is_backflush'] = $validated['consumption_policy'] !== 'direct_issue';
        $validated['policy_confirmed_at'] = now();
        $validated['policy_confirmed_by'] = auth()->id();

        $customerIds = $request->input('customer_ids', []);

        if (in_array($validated['classification'], ['RM', 'WIP'], true)) {
            $validated['model'] = null;
            $customerIds = [];
        }

        $gciPart->update($validated);
        $gciPart->customers()->sync($customerIds);

        // Sync vendors for RM
        if ($validated['classification'] === 'RM') {
            $gciPart->vendors()->sync($vendorIds);
        }

        // Auto-link RM to new FG BOMs (skip already linked)
        $bomLinked = 0;
        if ($validated['classification'] === 'RM' && !empty($destinationFgIds)) {
            $existingBomIds = BomItem::where('component_part_id', $gciPart->id)
                ->pluck('bom_id')->toArray();

            foreach ($destinationFgIds as $fgId) {
                $bom = Bom::where('part_id', $fgId)->first();
                if ($bom && !in_array($bom->id, $existingBomIds)) {
                    $nextLine = ($bom->items()->max('line_no') ?? 0) + 1;
                    BomItem::create([
                        'bom_id' => $bom->id,
                        'component_part_id' => $gciPart->id,
                        'component_part_no' => $gciPart->part_no,
                        'line_no' => $nextLine,
                        'usage_qty' => 1,
                        'make_or_buy' => 'buy',
                    ]);
                    $bomLinked++;
                }
            }
        }

        $msg = 'Part GCI updated.';
        if ($bomLinked > 0) {
            $msg .= " Linked to {$bomLinked} new FG BOM(s).";
        }

        return back()->with('success', $msg);
    }

    public function destroy(GciPart $gciPart)
    {
        // Pre-check all tables with RESTRICT FK (these block deletion and cause 500)
        $checks = [
            ['bom_item_substitutes', 'substitute_part_id', 'BOM Item Substitutes'],
            ['stock_opname_items', 'gci_part_id', 'Stock Opname Items'],
            ['osp_orders', 'gci_part_id', 'OSP Orders'],
            ['subcon_orders', 'gci_part_id', 'Subcon Orders'],
            ['outgoing_picking_fgs', 'gci_part_id', 'Picking FG Records'],
            ['production_orders', 'gci_part_id', 'Production Orders'],
            ['outgoing_delivery_planning_lines', 'gci_part_id', 'Delivery Planning Lines'],
        ];

        $references = [];
        foreach ($checks as [$table, $column, $label]) {
            try {
                if (DB::table($table)->where($column, $gciPart->id)->exists()) {
                    $references[] = $label;
                }
            } catch (\Throwable $e) {
                // Table might not exist
            }
        }

        if (!empty($references)) {
            return back()->with('error',
                'Tidak bisa hapus part "' . $gciPart->part_no . '" karena masih digunakan di: '
                . implode(', ', $references)
                . '. Hapus referensi tersebut terlebih dahulu atau set status part ke inactive.'
            );
        }

        try {
            $gciPart->delete();
            return back()->with('success', 'Part GCI deleted.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with('error',
                    'Tidak bisa hapus part "' . $gciPart->part_no . '" karena masih ada referensi di database. Set status ke inactive sebagai alternatif.'
                );
            }
            return back()->with('error', 'Gagal menghapus part: ' . $e->getMessage());
        }
    }

    public function storeSubstitute(Request $request, GciPart $gciPart)
    {
        $validated = $request->validate([
            'fg_part_id' => ['required', 'exists:gci_parts,id'],
            'substitute_part_id' => ['required', 'exists:gci_parts,id'],
            'ratio' => ['nullable', 'numeric', 'min:0.0001'],
            'priority' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $bom = Bom::where('part_id', $validated['fg_part_id'])->latest()->first();
        if (!$bom) {
            return back()->with('error', 'BOM tidak ditemukan untuk FG tersebut.');
        }

        $bomItem = BomItem::where('bom_id', $bom->id)
            ->where('component_part_id', $gciPart->id)
            ->first();
        if (!$bomItem) {
            return back()->with('error', 'RM ini belum ada di BOM FG tersebut.');
        }

        $substitutePart = GciPart::find($validated['substitute_part_id']);

        BomItemSubstitute::updateOrCreate(
            [
                'bom_item_id' => $bomItem->id,
                'substitute_part_id' => (int) $validated['substitute_part_id'],
            ],
            [
                'substitute_part_no' => $substitutePart->part_no,
                'ratio' => $validated['ratio'] ?? 1,
                'priority' => $validated['priority'] ?? 1,
                'status' => $validated['status'] ?? 'active',
                'notes' => $validated['notes'] ? trim($validated['notes']) : null,
            ],
        );

        return back()->with('success', 'Substitute saved.');
    }

    public function updateSubstitute(Request $request, BomItemSubstitute $substitute)
    {
        $validated = $request->validate([
            'substitute_part_id' => ['required', 'exists:gci_parts,id'],
            'ratio' => ['nullable', 'numeric', 'min:0.0001'],
            'priority' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $substitutePart = GciPart::find($validated['substitute_part_id']);

        $substitute->update([
            'substitute_part_id' => (int) $validated['substitute_part_id'],
            'substitute_part_no' => $substitutePart->part_no,
            'ratio' => $validated['ratio'] ?? 1,
            'priority' => $validated['priority'] ?? 1,
            'status' => $validated['status'] ?? 'active',
            'notes' => $validated['notes'] ? trim($validated['notes']) : null,
        ]);

        return back()->with('success', 'Substitute updated.');
    }

    public function destroySubstitute(BomItemSubstitute $substitute)
    {
        $substitute->delete();
        return back()->with('success', 'Substitute deleted.');
    }
}
