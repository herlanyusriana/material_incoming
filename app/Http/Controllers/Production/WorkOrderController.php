<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\GciPart;
use App\Models\MrpProductionPlan;
use App\Models\OutgoingDailyPlanCell;
use App\Models\WorkOrder;
use App\Models\WorkOrderHistory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $sourceType = (string) $request->query('source_type', '');
        $search = trim((string) $request->query('search', ''));

        $orders = WorkOrder::query()
            ->with('fgPart:id,part_no,part_name')
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->when($sourceType !== '', fn($q) => $q->where('source_type', $sourceType))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('wo_no', 'like', '%' . $search . '%')
                        ->orWhereHas('fgPart', function ($p) use ($search) {
                            $p->where('part_no', 'like', '%' . $search . '%')
                                ->orWhere('part_name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('production.work-orders.index', compact('orders', 'status', 'sourceType', 'search'));
    }

    public function create(): View
    {
        return view('production.work-orders.create');
    }

    public function createData(Request $request): JsonResponse
    {
        $sourceType = (string) $request->query('source_type', 'manual');
        $result = [
            'source_type' => $sourceType,
            'candidates' => [],
            'fg_parts' => [],
        ];

        if ($sourceType === 'manual') {
            $result['fg_parts'] = GciPart::query()
                ->where('classification', 'FG')
                ->where('status', 'active')
                ->orderBy('part_no')
                ->get(['id', 'part_no', 'part_name'])
                ->map(fn($p) => [
                    'id' => $p->id,
                    'label' => trim($p->part_no . ' - ' . $p->part_name),
                ])
                ->values();
            return response()->json($result);
        }

        if ($sourceType === 'mrp') {
            $mrpQtyColumn = Schema::hasColumn('mrp_production_plans', 'planned_qty') ? 'planned_qty' : 'planned_order_rec';
            $result['candidates'] = MrpProductionPlan::query()
                ->with(['part:id,part_no,part_name', 'run:id,period'])
                ->where($mrpQtyColumn, '>', 0)
                ->orderByDesc('plan_date')
                ->orderByDesc('id')
                ->limit(300)
                ->get()
                ->map(fn($row) => [
                    'id' => $row->id,
                    'label' => trim(($row->run?->period ? '[MRP ' . $row->run->period . '] ' : '') . ($row->part?->part_no ?? '-') . ' - ' . number_format((float) data_get($row, $mrpQtyColumn, 0), 2) . ' @ ' . Carbon::parse($row->plan_date)->format('Y-m-d')),
                    'fg_part_id' => $row->part_id,
                    'fg_part_no' => $row->part?->part_no,
                    'fg_part_name' => $row->part?->part_name,
                    'qty_plan' => (float) data_get($row, $mrpQtyColumn, 0),
                    'plan_date' => Carbon::parse($row->plan_date)->format('Y-m-d'),
                ])
                ->values();
            return response()->json($result);
        }

        if ($sourceType === 'outgoing_daily') {
            $result['candidates'] = OutgoingDailyPlanCell::query()
                ->select('outgoing_daily_plan_cells.*')
                ->join('outgoing_daily_plan_rows as r', 'r.id', '=', 'outgoing_daily_plan_cells.row_id')
                ->with(['row.gciPart:id,part_no,part_name', 'row.plan:id,date_from,date_to'])
                ->where('outgoing_daily_plan_cells.qty', '>', 0)
                ->whereNotNull('r.gci_part_id')
                ->orderByDesc('outgoing_daily_plan_cells.plan_date')
                ->orderByDesc('outgoing_daily_plan_cells.id')
                ->limit(300)
                ->get()
                ->map(fn($cell) => [
                    'id' => $cell->id,
                    'label' => trim(($cell->row?->plan ? '[' . Carbon::parse($cell->row->plan->date_from)->format('Y-m-d') . ' .. ' . Carbon::parse($cell->row->plan->date_to)->format('Y-m-d') . '] ' : '') . ($cell->row?->gciPart?->part_no ?? '-') . ' - ' . number_format((float) $cell->qty, 0) . ' @ ' . Carbon::parse($cell->plan_date)->format('Y-m-d')),
                    'fg_part_id' => $cell->row?->gci_part_id,
                    'fg_part_no' => $cell->row?->gciPart?->part_no,
                    'fg_part_name' => $cell->row?->gciPart?->part_name,
                    'qty_plan' => (float) $cell->qty,
                    'plan_date' => Carbon::parse($cell->plan_date)->format('Y-m-d'),
                ])
                ->values();
            return response()->json($result);
        }

        return response()->json([
            'message' => 'Invalid source_type.',
            'source_type' => $sourceType,
            'candidates' => [],
        ], 422);
    }

    public function generate(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'source_type' => 'required|in:manual,mrp,outgoing_daily',
            'source_ref_id' => 'nullable|integer',
            'fg_part_id' => 'nullable|integer|exists:gci_parts,id',
            'qty_plan' => 'nullable|numeric|min:0.0001',
            'plan_date' => 'nullable|date',
            'priority' => 'nullable|integer|min:1|max:5',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $resolved = $this->resolveSourceData(
            $payload['source_type'],
            $payload['source_ref_id'] ?? null,
            $payload['fg_part_id'] ?? null,
            $payload['qty_plan'] ?? null,
            $payload['plan_date'] ?? null
        );

        if (!$resolved['ok']) {
            return back()->withInput()->with('error', $resolved['message']);
        }

        $fgPartId = (int) $resolved['fg_part_id'];
        $qtyPlan = (float) $resolved['qty_plan'];
        $planDate = Carbon::parse($resolved['plan_date'])->toDateString();
        $sourceRefModel = $resolved['source_ref_model'];

        $bom = Bom::activeVersion($fgPartId, $planDate);
        if (!$bom) {
            return back()->withInput()->with('error', 'BOM aktif untuk FG terpilih tidak ditemukan pada tanggal plan.');
        }

        $bom->load(['items.componentPart:id,part_no,part_name', 'items.machine:id,name', 'items.substitutes.part:id,part_no,part_name']);

        $userId = Auth::id();
        $sourcePayload = $resolved['source_payload'];

        $workOrder = DB::transaction(function () use (
            $fgPartId,
            $qtyPlan,
            $planDate,
            $payload,
            $sourcePayload,
            $sourceRefModel,
            $bom,
            $userId
        ) {
            $wo = WorkOrder::create([
                'wo_no' => WorkOrder::generateWoNo(),
                'fg_part_id' => $fgPartId,
                'qty_plan' => $qtyPlan,
                'plan_date' => $planDate,
                'status' => 'open',
                'priority' => (int) ($payload['priority'] ?? 3),
                'remarks' => $payload['remarks'] ?? null,
                'source_type' => $payload['source_type'],
                'source_ref_type' => $sourceRefModel ? get_class($sourceRefModel) : null,
                'source_ref_id' => $sourceRefModel?->id,
                'source_payload_json' => $sourcePayload,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $requirements = [];
            foreach ($bom->items as $item) {
                $netPerFg = (float) $item->net_required;
                $substitutes = $item->substitutes
                    ->map(fn($s) => [
                        'id' => $s->id,
                        'part_id' => $s->substitute_part_id,
                        'part_no' => $s->part?->part_no ?? $s->substitute_part_no,
                        'part_name' => $s->part?->part_name ?? null,
                        'ratio' => (float) $s->ratio,
                        'priority' => (int) $s->priority,
                        'status' => $s->status,
                    ])->values()->all();

                $wo->bomSnapshots()->create([
                    'bom_id' => $bom->id,
                    'bom_item_id' => $item->id,
                    'line_no' => (int) ($item->line_no ?? 0),
                    'component_part_id' => $item->component_part_id,
                    'component_part_no' => $item->componentPart?->part_no ?? $item->component_part_no,
                    'component_part_name' => $item->componentPart?->part_name,
                    'usage_qty' => (float) $item->usage_qty,
                    'scrap_factor' => (float) $item->scrap_factor,
                    'yield_factor' => (float) ($item->yield_factor ?: 1),
                    'net_required_per_fg' => $netPerFg,
                    'consumption_uom' => $item->consumption_uom,
                    'process_name' => $item->process_name,
                    'machine_name' => $item->machine?->name,
                    'material_name' => $item->material_name,
                    'material_spec' => $item->material_spec,
                    'material_size' => $item->material_size,
                    'make_or_buy' => $item->make_or_buy,
                    'substitutes_json' => $substitutes,
                ]);

                $key = (string) ($item->component_part_id ?: ('PN:' . ($item->component_part_no ?? '')));
                if (!isset($requirements[$key])) {
                    $requirements[$key] = [
                        'component_part_id' => $item->component_part_id,
                        'component_part_no' => $item->componentPart?->part_no ?? $item->component_part_no,
                        'component_part_name' => $item->componentPart?->part_name,
                        'uom' => $item->consumption_uom,
                        'qty_per_fg' => 0,
                    ];
                }
                $requirements[$key]['qty_per_fg'] += $netPerFg;
            }

            foreach ($requirements as $req) {
                $wo->requirementSnapshots()->create([
                    'component_part_id' => $req['component_part_id'],
                    'component_part_no' => $req['component_part_no'],
                    'component_part_name' => $req['component_part_name'],
                    'uom' => $req['uom'],
                    'qty_per_fg' => (float) $req['qty_per_fg'],
                    'qty_requirement' => (float) $req['qty_per_fg'] * (float) $qtyPlan,
                ]);
            }

            $this->logHistory($wo, 'created', null, [
                'wo_no' => $wo->wo_no,
                'source_type' => $wo->source_type,
                'source_ref_type' => $wo->source_ref_type,
                'source_ref_id' => $wo->source_ref_id,
                'fg_part_id' => $wo->fg_part_id,
                'qty_plan' => (float) $wo->qty_plan,
                'plan_date' => optional($wo->plan_date)->format('Y-m-d'),
                'status' => $wo->status,
            ], 'WO generated from source.');

            return $wo;
        });

        return redirect()
            ->route('production.work-orders.show', $workOrder)
            ->with('success', 'Work Order berhasil dibuat dengan snapshot BOM dan material requirement.');
    }

    public function show(WorkOrder $workOrder): View
    {
        $workOrder->load([
            'fgPart:id,part_no,part_name',
            'bomSnapshots',
            'requirementSnapshots',
            'histories.actor:id,name',
            'creator:id,name',
            'updater:id,name',
        ]);

        $fgParts = GciPart::query()
            ->where('classification', 'FG')
            ->where('status', 'active')
            ->orderBy('part_no')
            ->get(['id', 'part_no', 'part_name']);

        return view('production.work-orders.show', compact('workOrder', 'fgParts'));
    }

    public function update(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $validated = $request->validate([
            'fg_part_id' => 'required|integer|exists:gci_parts,id',
            'qty_plan' => 'required|numeric|min:0.0001',
            'plan_date' => 'required|date',
            'priority' => 'required|integer|min:1|max:5',
            'remarks' => 'nullable|string|max:1000',
            'routing_json' => 'nullable|string',
            'schedule_json' => 'nullable|string',
        ]);

        $before = [
            'fg_part_id' => $workOrder->fg_part_id,
            'qty_plan' => (float) $workOrder->qty_plan,
            'plan_date' => optional($workOrder->plan_date)->format('Y-m-d'),
            'priority' => (int) $workOrder->priority,
            'remarks' => $workOrder->remarks,
            'routing_json' => $workOrder->routing_json,
            'schedule_json' => $workOrder->schedule_json,
        ];

        $routingJson = $this->decodeOptionalJson($validated['routing_json'] ?? null, 'routing_json');
        if ($routingJson['error']) {
            return back()->withInput()->with('error', $routingJson['message']);
        }
        $scheduleJson = $this->decodeOptionalJson($validated['schedule_json'] ?? null, 'schedule_json');
        if ($scheduleJson['error']) {
            return back()->withInput()->with('error', $scheduleJson['message']);
        }

        $workOrder->update([
            'fg_part_id' => (int) $validated['fg_part_id'],
            'qty_plan' => (float) $validated['qty_plan'],
            'plan_date' => Carbon::parse($validated['plan_date'])->toDateString(),
            'priority' => (int) $validated['priority'],
            'remarks' => $validated['remarks'] ?? null,
            'routing_json' => $routingJson['value'],
            'schedule_json' => $scheduleJson['value'],
            'updated_by' => Auth::id(),
        ]);

        $after = [
            'fg_part_id' => $workOrder->fg_part_id,
            'qty_plan' => (float) $workOrder->qty_plan,
            'plan_date' => optional($workOrder->plan_date)->format('Y-m-d'),
            'priority' => (int) $workOrder->priority,
            'remarks' => $workOrder->remarks,
            'routing_json' => $workOrder->routing_json,
            'schedule_json' => $workOrder->schedule_json,
        ];

        $this->logHistory($workOrder, 'updated', $before, $after, 'WO edited after generation. Snapshots remain frozen.');

        return back()->with('success', 'WO berhasil diupdate. Snapshot tetap tidak berubah (freeze).');
    }

    public function updateStatus(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,qc,closed',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $current = (string) $workOrder->status;
        $target = (string) $validated['status'];

        $allowed = [
            'open' => ['in_progress'],
            'in_progress' => ['qc'],
            'qc' => ['closed'],
            'closed' => [],
        ];

        if ($current === $target) {
            return back()->with('success', 'Status WO tidak berubah.');
        }

        if (!in_array($target, $allowed[$current] ?? [], true)) {
            return back()->with('error', 'Transisi status tidak valid. Gunakan alur Open -> In Progress -> QC -> Closed.');
        }

        $before = ['status' => $current];
        $workOrder->update([
            'status' => $target,
            'updated_by' => Auth::id(),
        ]);
        $after = ['status' => $target];

        $this->logHistory($workOrder, 'status_changed', $before, $after, $validated['remarks'] ?? null);

        return back()->with('success', 'Status WO berhasil diubah ke ' . strtoupper(str_replace('_', ' ', $target)) . '.');
    }

    private function resolveSourceData(
        string $sourceType,
        ?int $sourceRefId,
        mixed $manualFgPartId,
        mixed $manualQty,
        mixed $manualPlanDate
    ): array {
        if ($sourceType === 'manual') {
            if (!$manualFgPartId || !$manualQty || !$manualPlanDate) {
                return ['ok' => false, 'message' => 'Untuk Manual, FG + Qty + Plan Date wajib diisi.'];
            }

            $part = GciPart::query()->whereKey($manualFgPartId)->first();
            if (!$part || $part->classification !== 'FG') {
                return ['ok' => false, 'message' => 'FG part manual tidak valid.'];
            }

            return [
                'ok' => true,
                'fg_part_id' => (int) $manualFgPartId,
                'qty_plan' => (float) $manualQty,
                'plan_date' => Carbon::parse($manualPlanDate)->toDateString(),
                'source_ref_model' => null,
                'source_payload' => [
                    'source_type' => 'manual',
                    'fg_part_id' => (int) $manualFgPartId,
                    'qty_plan' => (float) $manualQty,
                    'plan_date' => Carbon::parse($manualPlanDate)->toDateString(),
                ],
            ];
        }

        if (!$sourceRefId) {
            return ['ok' => false, 'message' => 'Source Reference wajib dipilih.'];
        }

        if ($sourceType === 'mrp') {
            $row = MrpProductionPlan::query()->with('part:id,classification')->find($sourceRefId);
            if (!$row) {
                return ['ok' => false, 'message' => 'MRP source reference tidak ditemukan.'];
            }
            if (!$row->part || $row->part->classification !== 'FG') {
                return ['ok' => false, 'message' => 'MRP reference tidak mengarah ke FG part valid.'];
            }
            $mrpQty = Schema::hasColumn('mrp_production_plans', 'planned_qty')
                ? (float) ($row->planned_qty ?? 0)
                : (float) ($row->planned_order_rec ?? 0);
            return [
                'ok' => true,
                'fg_part_id' => (int) $row->part_id,
                'qty_plan' => $mrpQty,
                'plan_date' => Carbon::parse($row->plan_date)->toDateString(),
                'source_ref_model' => $row,
                'source_payload' => [
                    'mrp_production_plan_id' => $row->id,
                    'mrp_run_id' => $row->mrp_run_id,
                    'fg_part_id' => (int) $row->part_id,
                    'qty_plan' => $mrpQty,
                    'plan_date' => Carbon::parse($row->plan_date)->toDateString(),
                ],
            ];
        }

        if ($sourceType === 'outgoing_daily') {
            $cell = OutgoingDailyPlanCell::query()->with('row.gciPart:id,classification')->find($sourceRefId);
            if (!$cell) {
                return ['ok' => false, 'message' => 'Daily Planning Outgoing reference tidak ditemukan.'];
            }

            $gciPart = $cell->row?->gciPart;
            if (!$gciPart || $gciPart->classification !== 'FG') {
                return ['ok' => false, 'message' => 'Daily Planning Outgoing reference tidak mengarah ke FG part valid.'];
            }

            return [
                'ok' => true,
                'fg_part_id' => (int) $cell->row->gci_part_id,
                'qty_plan' => (float) $cell->qty,
                'plan_date' => Carbon::parse($cell->plan_date)->toDateString(),
                'source_ref_model' => $cell,
                'source_payload' => [
                    'daily_plan_cell_id' => $cell->id,
                    'daily_plan_row_id' => $cell->row_id,
                    'fg_part_id' => (int) $cell->row->gci_part_id,
                    'qty_plan' => (float) $cell->qty,
                    'plan_date' => Carbon::parse($cell->plan_date)->toDateString(),
                ],
            ];
        }

        return ['ok' => false, 'message' => 'source_type tidak valid.'];
    }

    private function decodeOptionalJson(?string $raw, string $field): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return ['error' => false, 'value' => null];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ['error' => true, 'message' => "Field {$field} harus JSON valid."];
        }

        return ['error' => false, 'value' => $decoded];
    }

    private function logHistory(
        WorkOrder $workOrder,
        string $eventType,
        mixed $before,
        mixed $after,
        ?string $remarks = null
    ): WorkOrderHistory {
        return $workOrder->histories()->create([
            'event_type' => $eventType,
            'before_json' => $before,
            'after_json' => $after,
            'remarks' => $remarks,
            'acted_by' => Auth::id(),
        ]);
    }
}
