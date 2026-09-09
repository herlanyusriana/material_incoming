<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Production\ProductionWorkOrder;
use App\Models\NewSchema\Production\WoMaterialAllocation;
use App\Services\WoTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON API untuk Flutter "Material Tracker".
 * Mirrors WoTrackingController flows with JSON responses instead of Blade views.
 */
class WoTrackingApiController extends Controller
{
    public function __construct(private WoTrackingService $tracking)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $status = strtoupper(trim((string) $request->query('status', '')));
        $q = trim((string) $request->query('q', ''));

        $wos = ProductionWorkOrder::query()
            ->with(['gciPart:id,part_no,part_name,uom', 'requirements:id,work_order_id,gci_part_id,required_qty,consumption_policy', 'allocations:id,work_order_id,gci_part_id,qty_reserved,status'])
            ->when(in_array($status, ['PLANNED', 'RELEASED', 'IN_PRODUCTION', 'CLOSED', 'CANCELLED'], true), fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where('work_order_no', 'like', "%{$q}%"))
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $wos->map(fn (ProductionWorkOrder $wo) => [
                'id' => $wo->id,
                'work_order_no' => $wo->work_order_no,
                'status' => $wo->status,
                'qty_target' => (float) $wo->qty_target,
                'qty_actual' => (float) ($wo->qty_actual ?? 0),
                'start_date' => $wo->start_date?->toDateString(),
                'part_no' => $wo->gciPart?->part_no,
                'part_name' => $wo->gciPart?->part_name,
                'uom' => $wo->gciPart?->uom,
                'requirements_count' => $wo->requirements->count(),
                'allocations_count' => $wo->allocations->count(),
                'allocated_qty' => (float) $wo->allocations
                    ->whereIn('status', [WoMaterialAllocation::STATUS_RESERVED, WoMaterialAllocation::STATUS_CONSUMED])
                    ->sum('qty_reserved'),
            ]),
        ]);
    }

    public function show(ProductionWorkOrder $woTracking): JsonResponse
    {
        $woTracking->load(['gciPart', 'requirements.gciPart', 'allocations']);

        return response()->json([
            'data' => [
                'id' => $woTracking->id,
                'work_order_no' => $woTracking->work_order_no,
                'status' => $woTracking->status,
                'qty_target' => (float) $woTracking->qty_target,
                'qty_actual' => (float) ($woTracking->qty_actual ?? 0),
                'start_date' => $woTracking->start_date?->toDateString(),
                'part_no' => $woTracking->gciPart?->part_no,
                'part_name' => $woTracking->gciPart?->part_name,
                'uom' => $woTracking->gciPart?->uom,
                'requirements' => $woTracking->requirements->map(fn ($r) => [
                    'id' => $r->id,
                    'part_no' => $r->gciPart?->part_no,
                    'required_qty' => (float) $r->required_qty,
                    'policy' => $r->consumption_policy,
                ]),
                'allocations' => $woTracking->allocations->map(fn ($a) => [
                    'id' => $a->id,
                    'tag' => $a->tag,
                    'qty_reserved' => (float) $a->qty_reserved,
                    'status' => $a->status,
                ]),
            ],
        ]);
    }

    public function locateTag(Request $request, ProductionWorkOrder $woTracking): JsonResponse
    {
        $validated = $request->validate(['tag' => ['required', 'string', 'max:255']]);
        $info = $this->tracking->locateTag(trim($validated['tag']));

        $requirement = null;
        if (($info['gci_part_id'] ?? 0) > 0) {
            $requirement = $woTracking->requirements->firstWhere('gci_part_id', $info['gci_part_id']);
            if ($requirement) {
                $info['suggestion'] = $this->tracking->suggestPicks($woTracking, $requirement);
            }
        }
        $info['requirement_id'] = $requirement?->id;
        $info['requirement_qty'] = $requirement?->required_qty;

        return response()->json($info);
    }

    public function allocate(Request $request, ProductionWorkOrder $woTracking): JsonResponse
    {
        $validated = $request->validate([
            'tag' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0.0001'],
            'requirement_id' => ['required', 'integer'],
        ]);

        $requirement = $woTracking->requirements()->whereKey((int) $validated['requirement_id'])->firstOrFail();
        if ((int) $requirement->work_order_id !== (int) $woTracking->id) {
            abort(422, 'Requirement bukan milik WO ini.');
        }

        // Mobile flow tanpa input lokasi: resolve dari stok tag (lokasi dengan qty terbanyak).
        $locationCode = $validated['location_code'] ?? null;
        if (empty($locationCode)) {
            $locationCode = \App\Models\NewSchema\Inventory\InventoryLocationStock::query()
                ->where('gci_part_id', $requirement->gci_part_id)
                ->where('batch_no', trim($validated['tag']))
                ->where('qty_on_hand', '>', 0)
                ->orderByDesc('qty_on_hand')
                ->value('location_code');
        }

        $this->tracking->allocate(
            $woTracking,
            $requirement,
            trim($validated['tag']),
            (float) $validated['qty'],
            $locationCode
        );

        return response()->json(['ok' => true, 'message' => "Tag {$validated['tag']} dialokasikan."]);
    }

    public function deallocate(Request $request, ProductionWorkOrder $woTracking): JsonResponse
    {
        $validated = $request->validate(['allocation_id' => ['required', 'integer']]);

        $allocation = $woTracking->allocations()->whereKey((int) $validated['allocation_id'])->firstOrFail();
        if ($allocation->status !== WoMaterialAllocation::STATUS_RESERVED) {
            return response()->json([
                'ok' => false,
                'message' => 'Alokasi sudah dikonsumsi — tidak bisa dilepas.',
            ], 422);
        }

        $this->tracking->returnAllocation($allocation);

        return response()->json(['ok' => true, 'message' => 'Alokasi dikembalikan ke gudang.']);
    }

    public function release(ProductionWorkOrder $woTracking): JsonResponse
    {
        if (! in_array($woTracking->status, ['PLANNED', 'RELEASED'], true)) {
            return response()->json(['ok' => false, 'message' => 'WO tidak dalam status yang bisa di-release.'], 422);
        }

        $short = $woTracking->requirements->filter(function ($requirement) use ($woTracking) {
            $covered = (float) $woTracking->allocations()
                ->where('gci_part_id', $requirement->gci_part_id)
                ->whereIn('status', [WoMaterialAllocation::STATUS_RESERVED, WoMaterialAllocation::STATUS_CONSUMED])
                ->sum('qty_reserved');

            return $covered + 1e-9 < (float) $requirement->required_qty;
        });

        if ($short->isNotEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Material belum lengkap: ' . $short->map(fn ($r) => ($r->gciPart?->part_no ?? $r->component_part_no))->implode(', '),
            ], 422);
        }

        $woTracking->update(['status' => 'RELEASED', 'released_at' => now(), 'released_by' => auth()->id()]);

        return response()->json(['ok' => true, 'message' => 'WO released — material terkunci untuk WO ini.']);
    }

    public function postResult(Request $request, ProductionWorkOrder $woTracking): JsonResponse
    {
        $validated = $request->validate([
            'qty_good' => ['required', 'numeric', 'min:0'],
            'qty_ng' => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($validated, $woTracking) {
            $wo = ProductionWorkOrder::query()->whereKey($woTracking->id)->lockForUpdate()->firstOrFail();

            if (in_array($wo->status, ['CLOSED', 'CANCELLED'], true)) {
                return response()->json(['ok' => false, 'message' => 'WO sudah ditutup.'], 422);
            }

            $qtyGood = (float) $validated['qty_good'];
            $qtyNg = (float) ($validated['qty_ng'] ?? 0);

            $wo->load('requirements');
            foreach ($wo->requirements as $requirement) {
                if ($requirement->consumption_policy === 'direct_issue') {
                    continue;
                }
                $consumption = round((float) $requirement->required_qty * ($qtyGood / max((float) $wo->qty_target, 0.0001)), 4);
                $this->tracking->consumeForWo($wo, (int) $requirement->gci_part_id, $consumption);
            }

            $fgPart = $wo->gciPart;
            if ($qtyGood > 0 && $fgPart?->default_location) {
                InventoryLocationStock::updateStock(
                    (int) $fgPart->id,
                    strtoupper(trim((string) $fgPart->default_location)),
                    $qtyGood,
                    $wo->work_order_no,
                    $wo->work_order_no,
                    'WO_FG_OUTPUT',
                    "WO#{$wo->id}",
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    auth()->id()
                );
            }

            $wo->qty_actual = (float) $wo->qty_actual + $qtyGood;
            $wo->status = 'IN_PRODUCTION';
            $wo->save();

            return response()->json([
                'ok' => true,
                'message' => "Hasil diposting: good {$qtyGood}, NG {$qtyNg}.",
            ]);
        });
    }

    public function close(ProductionWorkOrder $woTracking): JsonResponse
    {
        try {
            $this->tracking->assertClosable($woTracking);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $woTracking->update(['status' => 'CLOSED', 'end_date' => now()->toDateString()]);

        return response()->json(['ok' => true, 'message' => 'WO closed. Semua material telah reconcile.']);
    }
}
