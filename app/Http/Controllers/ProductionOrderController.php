<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionInspection;
use App\Models\GciPart;
use App\Models\GciInventory;
use App\Models\LocationInventory;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductionOrderController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $month = $request->query('month');
        $status = strtolower(trim((string) $request->query('status', '')));
        $q = trim((string) $request->query('q', ''));
        $gciPartId = (int) $request->query('gci_part_id', 0);

        if ($month && preg_match('/^\\d{4}-\\d{2}$/', (string) $month)) {
            try {
                $from = now()->parse($month . '-01')->startOfMonth();
                $to = $from->copy()->endOfMonth();
                $dateFrom = $dateFrom ?: $from->toDateString();
                $dateTo = $dateTo ?: $to->toDateString();
            } catch (\Throwable) {
            }
        }

        $query = ProductionOrder::query()
            ->with(['part', 'machine', 'dailyPlanCell.row'])
            ->when($gciPartId > 0, fn($qr) => $qr->where('gci_part_id', $gciPartId))
            ->when($status !== '', fn($qr) => $qr->where('status', $status))
            ->when($dateFrom || $dateTo, function ($qr) use ($dateFrom, $dateTo) {
                $from = $dateFrom ? $dateFrom : '1900-01-01';
                $to = $dateTo ? $dateTo : '2999-12-31';
                $qr->whereBetween('plan_date', [$from, $to]);
            })
            ->when($q !== '', function ($qr) use ($q) {
                $s = strtoupper($q);
                $qr->where('production_order_number', 'like', '%' . $s . '%')
                    ->orWhereHas('part', function ($qp) use ($s) {
                        $qp->where('part_no', 'like', '%' . $s . '%')
                            ->orWhere('part_name', 'like', '%' . $s . '%')
                            ->orWhere('model', 'like', '%' . $s . '%');
                    });
            })
            ->latest();

        $orders = $query->paginate(20)->withQueryString();

        return view('production.orders.index', compact('orders', 'dateFrom', 'dateTo', 'month', 'status', 'q', 'gciPartId'));
    }

    public function create()
    {
        $machines = Machine::where('is_active', true)->orderBy('name')->get();
        return view('production.orders.create', compact('machines'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gci_part_id' => 'required|exists:gci_parts,id',
            'process_name' => 'nullable|string|max:255',
            'machine_id' => 'nullable|exists:machines,id',
            'die_name' => 'nullable|string|max:255',
            'plan_date' => 'required|date',
            'qty_planned' => 'required|numeric|min:1',
            'production_order_number' => 'required|unique:production_orders,production_order_number',
            'arrival_ids' => 'nullable|array',
            'arrival_ids.*' => 'integer|exists:arrivals,id',
        ]);

        $order = ProductionOrder::create([
            'production_order_number' => $validated['production_order_number'],
            'gci_part_id' => $validated['gci_part_id'],
            'process_name' => isset($validated['process_name']) && trim((string) $validated['process_name']) !== '' ? trim((string) $validated['process_name']) : null,
            'machine_id' => $validated['machine_id'] ?? null,
            'die_name' => isset($validated['die_name']) && trim((string) $validated['die_name']) !== '' ? trim((string) $validated['die_name']) : null,
            'plan_date' => $validated['plan_date'],
            'qty_planned' => $validated['qty_planned'],
            'status' => 'planned',
            'workflow_stage' => 'planned',
            'qty_actual' => 0,
            'created_by' => Auth::id(),
        ]);

        // Auto-generate WO transaction number on creation
        if (empty($order->transaction_no)) {
            $order->transaction_no = ProductionOrder::generateTransactionNo($validated['plan_date']);
            $order->save();
        }

        // Save traceability: WO ↔ SO
        if (!empty($validated['arrival_ids'])) {
            $order->arrivals()->sync($validated['arrival_ids']);
        }

        return redirect()->route('production.orders.show', $order);
    }

    public function show(Request $request, ProductionOrder $order)
    {
        $order->load([
            'part',
            'machine',
            'inspections.inspector',
            'creator',
            'materialRequester',
            'materialIssuer',
            'materialHandoverUser',
            'mrpRun',
            'dailyPlanCell.row.plan',
            'arrivals',
        ]);

        if ($request->ajax()) {
            return view('production.orders.partials.detail_content', compact('order'));
        }

        return view('production.orders.show', compact('order'));
    }

    public function checkMaterial(ProductionOrder $order)
    {
        // 1. Find active BOM for this part
        $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);

        if (!$bom) {
            return back()->with('error', 'Tidak ada BOM aktif untuk part ini. Buat BOM terlebih dahulu.');
        }

        // 2. Get material requirements from BOM explosion
        // This method handles recursive requirements and aggregates by part_no
        $requirements = $bom->getTotalMaterialRequirements($order->qty_planned);

        if (empty($requirements)) {
            return back()->with('error', 'BOM tidak memiliki komponen/material. Periksa BOM.');
        }

        // 3. Check each material against inventory
        $results = [];
        $allAvailable = true;

        foreach ($requirements as $req) {
            $partNo = $req['part_no'];
            $part = $req['part'];
            $needed = round($req['total_qty'], 4);
            $makeOrBuy = strtolower($req['make_or_buy'] ?? 'buy');

            // Skip free-issue items (provided by customer)
            if ($makeOrBuy === 'free_issue') {
                continue;
            }

            // Get inventory (on_hand)
            $inventory = GciInventory::where('gci_part_id', $part?->id)->first();
            $onHand = $inventory ? (float) $inventory->on_hand : 0;

            $sufficient = $onHand >= $needed;
            $shortage = $sufficient ? 0 : round($needed - $onHand, 4);

            if (!$sufficient) {
                $allAvailable = false;
            }

            $results[] = [
                'part_no' => $partNo,
                'part_name' => $part?->part_name ?? '-',
                'needed' => $needed,
                'on_hand' => $onHand,
                'sufficient' => $sufficient,
                'shortage' => $shortage,
                'uom' => $req['uom'] ?? '-',
            ];
        }

        // 4. Update WO status based on result
        if ($allAvailable) {
            // Reserve materials: move from on_hand to on_order
            $reservedMaterials = [];
            foreach ($requirements as $req) {
                $makeOrBuy = strtolower($req['make_or_buy'] ?? 'buy');
                if ($makeOrBuy === 'free_issue') {
                    continue;
                }

                $part = $req['part'];
                $needed = round($req['total_qty'], 4);
                if (!$part?->id || $needed <= 0) {
                    continue;
                }

                $inventory = GciInventory::firstOrCreate(
                    ['gci_part_id' => $part->id],
                    ['on_hand' => 0, 'on_order' => 0, 'as_of_date' => now()->toDateString()]
                );
                $inventory->reserve($needed);

                $reservedMaterials[] = [
                    'gci_part_id' => $part->id,
                    'part_no' => $req['part_no'],
                    'qty' => $needed,
                ];
            }

            $order->update([
                'status' => 'released',
                'workflow_stage' => 'material_ready',
                'reserved_materials' => $reservedMaterials,
            ]);

            return back()->with('success', 'Semua material tersedia & direservasi! WO status diperbarui ke RELEASED.')
                         ->with('material_check', $results);
        } else {
            $order->update([
                'status' => 'material_hold',
                'workflow_stage' => 'material_check',
            ]);

            $shortItems = array_filter($results, fn($r) => !$r['sufficient']);
            $shortCount = count($shortItems);
            return back()->with('error', "Material tidak cukup ($shortCount item kurang). Periksa tabel di bawah.")
                         ->with('material_check', $results);
        }
    }

    public function createMaterialRequest(ProductionOrder $order)
    {
        $requestLines = $this->buildMaterialRequestLines($order);

        if (empty($requestLines)) {
            return back()->with('error', 'Tidak ada RM BUY yang bisa dibuatkan material request dari BOM ini.');
        }

        $order->update([
            'material_request_lines' => $requestLines,
            'material_requested_at' => now(),
            'material_requested_by' => Auth::id(),
        ]);

        $shortageCount = collect($requestLines)->where('shortage_qty', '>', 0)->count();
        $message = $shortageCount > 0
            ? "Material request dibuat dengan {$shortageCount} item masih shortage."
            : 'Material request berhasil dibuat dari stok RM yang tersedia.';

        return back()->with('success', $message);
    }

    public function issueMaterial(ProductionOrder $order)
    {
        $requestLines = collect($order->material_request_lines ?? []);

        if ($requestLines->isEmpty()) {
            return back()->with('error', 'Buat material request terlebih dahulu sebelum issue material.');
        }

        if ($order->material_issued_at) {
            return back()->with('error', 'WH supply untuk work order ini sudah pernah diposting.');
        }

        $shortageCount = $requestLines->where('shortage_qty', '>', 0)->count();
        if ($shortageCount > 0) {
            return back()->with('error', 'Masih ada shortage pada material request. WH supply diblokir.');
        }

        $issueLines = [];
        $sourceReference = 'PROD#' . ($order->production_order_number ?: $order->id);

        DB::transaction(function () use ($order, $requestLines, &$issueLines, $sourceReference) {
            foreach ($requestLines as $line) {
                $issuedAllocations = [];

                foreach (($line['allocations'] ?? []) as $allocation) {
                    $partId = (int) ($allocation['part_id'] ?? 0);
                    $locationCode = (string) ($allocation['location_code'] ?? '');
                    $batchNo = (string) ($allocation['batch_no'] ?? '');
                    $qty = (float) ($allocation['request_qty'] ?? 0);

                    if ($partId <= 0 || $locationCode === '' || $qty <= 0) {
                        continue;
                    }

                    LocationInventory::consumeStock(
                        $partId,
                        $locationCode,
                        $qty,
                        $batchNo !== '' ? $batchNo : null,
                        null,
                        'PRODUCTION_ISSUE',
                        $sourceReference
                    );

                    $issuedAllocations[] = array_merge($allocation, [
                        'issued_qty' => $qty,
                    ]);
                }

                $issueLines[] = [
                    'component_gci_part_id' => (int) ($line['component_gci_part_id'] ?? 0),
                    'component_part_no' => (string) ($line['component_part_no'] ?? '-'),
                    'component_part_name' => (string) ($line['component_part_name'] ?? '-'),
                    'uom' => (string) ($line['uom'] ?? '-'),
                    'required_qty' => (float) ($line['required_qty'] ?? 0),
                    'issued_qty' => collect($issuedAllocations)->sum('issued_qty'),
                    'allocations' => $issuedAllocations,
                ];
            }

            $order->update([
                'material_issue_lines' => $issueLines,
                'material_issued_at' => now(),
                'material_issued_by' => Auth::id(),
            ]);
        });

        return back()->with('success', 'WH supply ke production berhasil diposting dari inventory lokasi.');
    }

    public function handoverMaterial(Request $request, ProductionOrder $order)
    {
        $validated = $request->validate([
            'handover_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($order->material_issue_lines) || !$order->material_issued_at) {
            return back()->with('error', 'WH supply belum diposting. Lakukan supply material dulu sebelum serah terima.');
        }

        if ($order->material_handed_over_at) {
            return back()->with('error', 'Serah terima material untuk work order ini sudah tercatat.');
        }

        $order->update([
            'material_handed_over_at' => now(),
            'material_handed_over_by' => Auth::id(),
            'material_handover_notes' => isset($validated['handover_notes']) && trim((string) $validated['handover_notes']) !== ''
                ? trim((string) $validated['handover_notes'])
                : null,
        ]);

        return back()->with('success', 'Serah terima material ke production berhasil dicatat.');
    }

    public function releaseKanban(ProductionOrder $order)
    {
        if ($order->status !== 'planned') {
            return back()->with('error', 'Only planned orders can be released to Kanban.');
        }

        $order->update([
            'status' => 'kanban_released',
            'workflow_stage' => 'kanban_released',
            'released_at' => now(),
            'released_by' => Auth::id(),
        ]);

        return back()->with('success', 'Kanban released.');
    }

    // Workflow Transitions

    public function startProduction(ProductionOrder $order)
    {
        if ($order->status !== 'released') {
            return back()->with('error', 'Order must be Released to start.');
        }

        if (!empty($order->material_request_lines) && !$order->material_issued_at) {
            return back()->with('error', 'Material request sudah ada, tapi WH supply belum diposting. Lakukan supply material dulu.');
        }

        if ($order->material_issued_at && !$order->material_handed_over_at) {
            return back()->with('error', 'WH supply sudah diposting, tapi serah terima ke production belum dicatat.');
        }

        $order->update([
            'status' => 'in_production',
            'workflow_stage' => 'first_article_inspection',
            'start_time' => now(),
        ]);

        // Initial transition to First Article Inspection
        $this->createInspection($order, 'first_article');

        return back()->with('success', 'Production started.');
    }

    public function createInspection(ProductionOrder $order, $type)
    {
        ProductionInspection::create([
            'production_order_id' => $order->id,
            'type' => $type,
            'status' => 'pending',
        ]);
    }

    public function finishProduction(ProductionOrder $order)
    {
        // Move to Final Inspection stage (inventory update happens on Kanban Update after final pass).
        if ($order->status !== 'in_production') {
            return back()->with('error', 'Order must be In Production to finish production.');
        }

        $order->update([
            'workflow_stage' => 'final_inspection',
            'end_time' => now(),
            // Keep qty_actual for Kanban Update; default to planned for convenience.
            'qty_actual' => $order->qty_actual > 0 ? $order->qty_actual : $order->qty_planned,
        ]);

        // Ensure final inspection exists
        if (!$order->inspections()->where('type', 'final')->exists()) {
            $this->createInspection($order, 'final');
        }

        return back()->with('success', 'Production finished. Please complete Final Inspection, then Kanban Update.');
    }

    public function kanbanUpdate(Request $request, ProductionOrder $order)
    {
        $validated = $request->validate([
            'qty_good' => ['required', 'numeric', 'min:0'],
            'qty_ng' => ['nullable', 'numeric', 'min:0'],
        ]);

        $final = $order->inspections()->where('type', 'final')->latest('id')->first();
        if (!$final || $final->status !== 'pass') {
            return back()->with('error', 'Final inspection must be PASS before Kanban Update.');
        }

        if (!in_array($order->workflow_stage, ['final_inspection', 'kanban_update', 'stock_update'], true)) {
            return back()->with('error', 'Order is not ready for Kanban Update.');
        }

        $qtyGood = (float) $validated['qty_good'];
        $qtyNg = (float) ($validated['qty_ng'] ?? 0);

        DB::transaction(function () use ($order, $qtyGood, $qtyNg) {
            $order->update([
                'qty_actual' => $qtyGood,
                'qty_ng' => $qtyNg,
                'kanban_updated_at' => now(),
                'kanban_updated_by' => Auth::id(),
                'workflow_stage' => 'stock_update',
            ]);

            // Add FG to LocationInventory (source of truth) → auto-sync ke gci_inventories via boot event
            $part = $order->part;
            $defaultLocation = $part?->default_location;
            if ($defaultLocation && $qtyGood > 0) {
                LocationInventory::updateStock(
                    null,
                    strtoupper(trim($defaultLocation)),
                    $qtyGood,
                    null,
                    now()->toDateString(),
                    $order->gci_part_id,
                    'PRODUCTION_OUTPUT',
                    'PROD#' . $order->order_no
                );
            }

            // Backflush components
            $reserved = $order->reserved_materials;

            if (!empty($reserved)) {
                // Reservation-based: consume from on_order, return excess to on_hand
                $reservedMap = collect($reserved)->keyBy('gci_part_id');
                $bom = Bom::where('part_id', $order->gci_part_id)->latest()->first();

                if ($bom) {
                    foreach ($bom->items as $item) {
                        $mob = strtolower((string) ($item->make_or_buy ?? 'buy'));
                        if ($mob === 'free_issue') {
                            continue;
                        }

                        $consumedQty = (float) ($item->net_required ?? $item->usage_qty ?? 0) * $qtyGood;
                        $partId = $item->component_part_id;
                        $reservedQty = (float) ($reservedMap[$partId]['qty'] ?? 0);

                        $compInv = GciInventory::firstOrCreate(
                            ['gci_part_id' => $partId],
                            ['on_hand' => 0, 'on_order' => 0, 'as_of_date' => now()->toDateString()]
                        );

                        // Consume what was used from on_order
                        $compInv->consume(min($consumedQty, $reservedQty));

                        // Return excess reservation to on_hand (produced less than planned)
                        $excess = $reservedQty - $consumedQty;
                        if ($excess > 0) {
                            $compInv->release($excess);
                        }
                    }
                }

                $order->update(['reserved_materials' => null]);
            } else {
                // Legacy: no reservation, backflush directly from on_hand
                $bom = Bom::where('part_id', $order->gci_part_id)->latest()->first();
                if ($bom) {
                    foreach ($bom->items as $item) {
                        $mob = strtolower((string) ($item->make_or_buy ?? 'buy'));
                        if ($mob === 'free_issue') {
                            continue;
                        }
                        $consumedQty = (float) ($item->net_required ?? $item->usage_qty ?? 0) * $qtyGood;
                        if ($consumedQty <= 0) {
                            continue;
                        }

                        $compInv = GciInventory::firstOrCreate(
                            ['gci_part_id' => $item->component_part_id],
                            ['on_hand' => 0, 'on_order' => 0, 'as_of_date' => now()->toDateString()]
                        );
                        $compInv->decrement('on_hand', $consumedQty);
                        $compInv->update(['as_of_date' => now()->toDateString()]);
                    }
                }
            }

            $order->update([
                'status' => 'completed',
                'workflow_stage' => 'finished',
            ]);
        });

        return back()->with('success', 'Kanban updated and inventory posted.');
    }

    public function cancel(ProductionOrder $order)
    {
        if ($order->status === 'completed') {
            return back()->with('error', 'Cannot cancel a completed order.');
        }

        DB::transaction(function () use ($order) {
            // Release reserved materials back to on_hand
            $reserved = $order->reserved_materials;
            if (!empty($reserved)) {
                foreach ($reserved as $mat) {
                    $compInv = GciInventory::where('gci_part_id', $mat['gci_part_id'])->first();
                    if ($compInv) {
                        $compInv->release((float) $mat['qty']);
                    }
                }
                $order->update(['reserved_materials' => null]);
            }

            $order->update([
                'status' => 'cancelled',
            ]);
        });

        return back()->with('success', 'Work order cancelled. Reserved materials returned to inventory.');
    }

    private function buildMaterialRequestLines(ProductionOrder $order): array
    {
        $order->loadMissing(['part']);

        $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
        if (!$bom) {
            return [];
        }

        $bomItems = $bom->items()
            ->with([
                'componentPart',
                'incomingPart',
                'substitutes.part',
                'substitutes.incomingPart',
            ])
            ->get();

        $lines = [];

        foreach ($bomItems as $item) {
            $makeOrBuy = strtoupper(trim((string) ($item->make_or_buy ?? '')));
            if (!in_array($makeOrBuy, ['BUY', 'B', 'PURCHASE'], true)) {
                continue;
            }

            $requiredQty = round((float) ($item->net_required ?? $item->usage_qty ?? 0) * (float) $order->qty_planned, 4);
            if ($requiredQty <= 0) {
                continue;
            }

            $candidateParts = collect();
            if ($item->incomingPart) {
                $candidateParts->push([
                    'type' => 'primary',
                    'part_id' => (int) $item->incomingPart->id,
                    'part_no' => (string) ($item->incomingPart->part_no ?? '-'),
                    'part_name' => (string) ($item->incomingPart->part_name ?? '-'),
                ]);
            }

            foreach (($item->substitutes ?? collect()) as $substitute) {
                if (!$substitute->incomingPart) {
                    continue;
                }

                $candidateParts->push([
                    'type' => 'substitute',
                    'part_id' => (int) $substitute->incomingPart->id,
                    'part_no' => (string) ($substitute->incomingPart->part_no ?? $substitute->substitute_part_no ?? '-'),
                    'part_name' => (string) ($substitute->incomingPart->part_name ?? $substitute->part?->part_name ?? '-'),
                ]);
            }

            $candidateParts = $candidateParts
                ->filter(fn ($part) => !empty($part['part_id']))
                ->unique('part_id')
                ->values();

            if ($candidateParts->isEmpty()) {
                $lines[] = [
                    'component_gci_part_id' => (int) ($item->component_part_id ?? 0),
                    'component_part_no' => (string) ($item->componentPart?->part_no ?? $item->component_part_no ?? '-'),
                    'component_part_name' => (string) ($item->componentPart?->part_name ?? '-'),
                    'uom' => (string) ($item->consumption_uom ?? $item->componentPart?->uom ?? 'PCS'),
                    'required_qty' => $requiredQty,
                    'available_qty' => 0,
                    'shortage_qty' => $requiredQty,
                    'allocations' => [],
                    'notes' => 'Incoming RM part belum dipetakan pada BOM.',
                ];
                continue;
            }

            $remaining = $requiredQty;
            $allocations = [];

            foreach ($candidateParts as $candidate) {
                if ($remaining <= 0) {
                    break;
                }

                $stocks = LocationInventory::query()
                    ->where('part_id', $candidate['part_id'])
                    ->where('qty_on_hand', '>', 0)
                    ->orderByRaw('production_date IS NULL')
                    ->orderBy('production_date')
                    ->orderBy('batch_no')
                    ->orderBy('location_code')
                    ->get();

                foreach ($stocks as $stock) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $available = (float) $stock->qty_on_hand;
                    if ($available <= 0) {
                        continue;
                    }

                    $pickedQty = min($available, $remaining);
                    $remaining = round($remaining - $pickedQty, 4);

                    $allocations[] = [
                        'source_type' => $candidate['type'],
                        'part_id' => $candidate['part_id'],
                        'part_no' => $candidate['part_no'],
                        'part_name' => $candidate['part_name'],
                        'location_code' => (string) $stock->location_code,
                        'batch_no' => (string) ($stock->batch_no ?? ''),
                        'qty_on_hand' => $available,
                        'request_qty' => $pickedQty,
                    ];
                }
            }

            $availableQty = collect($allocations)->sum('request_qty');
            $lines[] = [
                'component_gci_part_id' => (int) ($item->component_part_id ?? 0),
                'component_part_no' => (string) ($item->componentPart?->part_no ?? $item->component_part_no ?? '-'),
                'component_part_name' => (string) ($item->componentPart?->part_name ?? '-'),
                'uom' => (string) ($item->consumption_uom ?? $item->componentPart?->uom ?? 'PCS'),
                'required_qty' => $requiredQty,
                'available_qty' => $availableQty,
                'shortage_qty' => max(0, round($requiredQty - $availableQty, 4)),
                'allocations' => $allocations,
                'notes' => null,
            ];
        }

        return $lines;
    }
}
