<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\Bom;
use App\Models\Receive;
use App\Models\LocationInventory;
use App\Models\GciInventory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class WarehouseApiController extends Controller
{
    private function isWarehouseScannableRm(string $makeOrBuy): bool
    {
        $normalized = strtoupper(trim($makeOrBuy));
        return in_array($normalized, ['BUY', 'B', 'PURCHASE', 'FREE_ISSUE', 'FREE ISSUE', 'FI'], true);
    }

    private function buildSupplyStatus(ProductionOrder $order): array
    {
        return [
            'wh_supply_posted' => !is_null($order->material_issued_at),
            'wh_supply_at' => $order->material_issued_at ? $order->material_issued_at->toDateTimeString() : null,
            'line_received' => !is_null($order->material_handed_over_at),
            'line_received_at' => $order->material_handed_over_at ? $order->material_handed_over_at->toDateTimeString() : null,
        ];
    }

    public function pendingWorkOrders(Request $request)
    {
        $selectedDate = trim((string) $request->query('date', ''));
        $targetDate = $selectedDate !== ''
            ? Carbon::parse($selectedDate)->toDateString()
            : now()->toDateString();

        $orders = ProductionOrder::with('part')
            ->whereDate('plan_date', $targetDate)
            ->whereIn('status', ['planned', 'kanban_released', 'material_hold', 'resource_hold', 'released'])
            ->whereNull('material_handed_over_at')
            ->orderBy('plan_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'wo_number' => $order->production_order_number ?? $order->transaction_no,
                    'part_no' => $order->part?->part_no ?? 'Unknown',
                    'plan_date' => $order->plan_date ? Carbon::parse($order->plan_date)->toDateString() : null,
                    'qty_planned' => (int) $order->qty_planned,
                    'status' => $order->status,
                    'supply_status' => $this->buildSupplyStatus($order),
                ];
            });

        return response()->json([
            'status' => 'success',
            'meta' => [
                'date' => $targetDate,
            ],
            'data' => $orders,
        ]);
    }

    public function getWorkOrder($id)
    {
        $order = ProductionOrder::findOrFail($id);
        $issueLines = $order->material_issue_lines ?? [];
        $scanProgress = collect($issueLines)
            ->reduce(function (array $carry, array $line) {
                $gciPartId = (int) ($line['gci_part_id'] ?? 0);
                $partNo = strtoupper(trim((string) ($line['part_no'] ?? '')));
                $key = $gciPartId > 0 ? 'gci:' . $gciPartId : 'part:' . $partNo;

                if (!isset($carry[$key])) {
                    $carry[$key] = [
                        'scanned_qty' => 0.0,
                        'scanned_tags' => 0,
                    ];
                }

                $carry[$key]['scanned_qty'] += (float) ($line['qty'] ?? $line['issued_qty'] ?? 0);
                $carry[$key]['scanned_tags']++;

                return $carry;
            }, []);
        
        $requirements = [];

        if (!empty($order->material_request_lines)) {
            foreach (($order->material_request_lines ?? []) as $line) {
                $requirements[] = [
                    'gci_part_id' => (int) ($line['component_gci_part_id'] ?? 0),
                    'part_no' => (string) ($line['component_part_no'] ?? 'Unknown'),
                    'part_name' => (string) ($line['component_part_name'] ?? ''),
                    'uom' => (string) ($line['uom'] ?? 'PCS'),
                    'total_qty' => (float) ($line['required_qty'] ?? 0),
                    'allocated_qty' => (float) ($line['available_qty'] ?? 0),
                    'shortage_qty' => (float) ($line['shortage_qty'] ?? 0),
                    'allocations' => array_values($line['allocations'] ?? []),
                    'notes' => $line['notes'] ?? null,
                ];
            }
        } else {
            $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
            if ($bom) {
                $reqs = $bom->getTotalMaterialRequirements($order->qty_planned);
                foreach ($reqs as $req) {
                    if (!$this->isWarehouseScannableRm((string) ($req['make_or_buy'] ?? ''))) {
                        continue;
                    }

                    $requirements[] = [
                        'gci_part_id' => (int) ($req['part']?->id ?? 0),
                        'part_no' => (string) ($req['part']?->part_no ?? $req['part_no'] ?? 'Unknown'),
                        'part_name' => (string) ($req['part']?->part_name ?? ''),
                        'uom' => (string) ($req['uom'] ?? 'PCS'),
                        'total_qty' => (float) ($req['total_qty'] ?? 0),
                        'allocated_qty' => 0.0,
                        'shortage_qty' => 0.0,
                        'allocations' => [],
                        'notes' => 'Material request WO belum dibuat, daftar diambil langsung dari BOM.',
                    ];
                }
            }
        }

        $requirements = collect($requirements)
            ->map(function (array $requirement) use ($scanProgress) {
                $gciPartId = (int) ($requirement['gci_part_id'] ?? 0);
                $partNo = strtoupper(trim((string) ($requirement['part_no'] ?? '')));
                $key = $gciPartId > 0 ? 'gci:' . $gciPartId : 'part:' . $partNo;
                $progress = $scanProgress[$key] ?? ['scanned_qty' => 0.0, 'scanned_tags' => 0];
                $requiredQty = (float) ($requirement['total_qty'] ?? 0);
                $scannedQty = round((float) ($progress['scanned_qty'] ?? 0), 4);
                $remainingQty = max(0, round($requiredQty - $scannedQty, 4));

                if ($scannedQty <= 0) {
                    $scanStatus = 'not_scanned';
                } elseif ($remainingQty > 0) {
                    $scanStatus = 'partial';
                } else {
                    $scanStatus = 'complete';
                }

                return array_merge($requirement, [
                    'scanned_qty' => $scannedQty,
                    'scanned_tags' => (int) ($progress['scanned_tags'] ?? 0),
                    'remaining_qty_to_scan' => $remainingQty,
                    'scan_status' => $scanStatus,
                ]);
            })
            ->sortBy([
                fn (array $line) => match ($line['scan_status'] ?? 'not_scanned') {
                    'not_scanned' => 0,
                    'partial' => 1,
                    default => 2,
                },
                fn (array $line) => $line['part_no'] ?? '',
            ])
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $order->id,
                'requirements' => $requirements,
                'issue_lines' => $issueLines,
                'supply_status' => $this->buildSupplyStatus($order),
            ]
        ]);
    }

    public function scanTag(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);
        $validated = $request->validate([
            'tag_no' => 'required|string',
        ]);

        $tags = $order->material_issue_lines ?? [];
        $tagNo = $validated['tag_no'];

        // Cek jika tag sudah pernah discan
        foreach ($tags as $tag) {
            if (($tag['tag_number'] ?? '') === $tagNo) {
                return response()->json(['status' => 'error', 'message' => 'Tag ini sudah pernah di-scan pada WO ini!'], 422);
            }
        }

        // Cari data tag fisik berdasarkan sistem barcode Incoming Material (Receives)
        $gciPartId = null;
        $partId = null;
        $qtyAvailable = 0;
        $partNo = 'Unknown';
        $locationCode = null;
        $traceability = [];

        // 1. Cek apakah barang sudah masuk rak (Location Inventory)
        $locInv = LocationInventory::where('batch_no', $tagNo)->with('part', 'gciPart')->first();
        if ($locInv) {
            $gciPartId = $locInv->gci_part_id;
            $partId = $locInv->part_id;
            $qtyAvailable = $locInv->qty_on_hand;
            $partNo = $locInv->part?->part_no ?? $locInv->gciPart?->part_no ?? 'Unknown';
            $locationCode = $locInv->location_code;
        } else {
            // 2. Kalau tag masih ada di Incoming/Putaway Queue, blokir dulu.
            $receive = Receive::where('tag', $tagNo)->with('arrivalItem.part', 'arrivalItem.gciPartVendor')->first();
            if ($receive) {
                $arrItem = $receive->arrivalItem;
                $partNo = $arrItem?->part?->part_no
                    ?? $arrItem?->gciPartVendor?->vendor_part_no
                    ?? 'Unknown';

                return response()->json([
                    'status' => 'error',
                    'message' => "Tag $tagNo untuk material $partNo masih ada di area incoming/putaway. Putaway ke lokasi gudang dulu sebelum supply ke produksi.",
                ], 422);
            }
        }

        if (!$gciPartId || $qtyAvailable <= 0) {
            return response()->json(['status' => 'error', 'message' => "Label/Tag tidak dikenali atau qty kosong. Pastikan ini Label RM GCI!"], 422);
        }

        // Cek apakah material ini dibutuhkan di BOM mesin
        $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
        if (!$bom) {
            return response()->json(['status' => 'error', 'message' => "Mesin ini tidak memiliki BOM aktif!"], 422);
        }

        $reqs = $bom->getTotalMaterialRequirements($order->qty_planned);
        $materialSesuaiBom = false;

        foreach ($reqs as $req) {
            if ($this->isWarehouseScannableRm((string) ($req['make_or_buy'] ?? ''))
                && $req['part']?->id === $gciPartId) {
                $materialSesuaiBom = true;
                break;
            }
        }

        if (!$materialSesuaiBom) {
            return response()->json(['status' => 'error', 'message' => "ERROR: Material {$partNo} TIDAK ADA dalam resep BOM mesin ini!"], 422);
        }

        // Jika lolos validasi, save ke lines
        $tags[] = [
            'tag_number' => $tagNo,
            'part_no' => $partNo,
            'qty' => $qtyAvailable,
            'gci_part_id' => $gciPartId,
            'part_id' => $partId,
            'location_code' => $locationCode,
            'traceability' => $traceability,
            'scanned_at' => now()->toDateTimeString(),
        ];
        
        $order->update(['material_issue_lines' => $tags]);

        return response()->json(['status' => 'success', 'data' => $tags]);
    }

    public function deleteTag(Request $request, $id, $tagNo)
    {
        $order = ProductionOrder::findOrFail($id);
        $tags = $order->material_issue_lines ?? [];

        // Filter out the tag that matches
        $filtered = array_values(array_filter($tags, function($tag) use ($tagNo) {
            return ($tag['tag_number'] ?? '') !== $tagNo;
        }));

        $order->update(['material_issue_lines' => $filtered]);

        return response()->json(['status' => 'success', 'data' => $filtered]);
    }

    public function handover(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);

        if (!$order->material_issued_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Supply material dari WH belum diposting. Posting supply dulu sebelum serah terima ke line.',
            ], 422);
        }

        $order->update([
            'material_handed_over_at' => now(),
            'material_handed_over_by' => auth()->id() ?? 1,
            'status' => $order->status === 'planned' ? 'kanban_released' : $order->status
        ]);
        
        return response()->json([
            'status' => 'success',
            'data' => $this->buildSupplyStatus($order->fresh()),
        ]);
    }

    public function postSupply(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);
        $tags = collect($order->material_issue_lines ?? []);

        if ($tags->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Belum ada tag supply yang discan untuk WO ini.',
            ], 422);
        }

        if ($order->material_issued_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Supply material dari WH untuk WO ini sudah pernah diposting.',
            ], 422);
        }

        $remainingReservedByGciPart = collect($order->material_request_lines ?? [])
            ->mapWithKeys(function ($line) {
                return [
                    (int) ($line['component_gci_part_id'] ?? 0) => (float) ($line['required_qty'] ?? 0),
                ];
            })
            ->filter(fn ($qty, $gciPartId) => $gciPartId > 0 && $qty > 0)
            ->all();

        DB::transaction(function () use ($order, $tags, &$remainingReservedByGciPart) {
            $sourceReference = 'PROD#' . ($order->production_order_number ?: $order->id);
            $postedIssueLines = [];

            foreach ($tags as $tag) {
                $tagNo = (string) ($tag['tag_number'] ?? '');
                $partNo = (string) ($tag['part_no'] ?? '-');
                $qty = (float) ($tag['qty'] ?? 0);
                $locationCode = (string) ($tag['location_code'] ?? '');
                $partId = (int) ($tag['part_id'] ?? 0);
                $gciPartId = (int) ($tag['gci_part_id'] ?? 0);
                $traceability = is_array($tag['traceability'] ?? null) ? $tag['traceability'] : [];

                if ($tagNo === '' || $qty <= 0 || $locationCode === '' || ($partId <= 0 && $gciPartId <= 0)) {
                    throw new \RuntimeException("Data tag $tagNo belum lengkap untuk posting supply.");
                }

                LocationInventory::consumeStock(
                    $partId > 0 ? $partId : null,
                    $locationCode,
                    $qty,
                    $tagNo,
                    $gciPartId > 0 ? $gciPartId : null,
                    'PRODUCTION_ISSUE',
                    $sourceReference,
                    array_merge([
                        'source_tag' => $tagNo,
                    ], $traceability)
                );

                if ($gciPartId > 0) {
                    $inventory = GciInventory::firstOrCreate(
                        ['gci_part_id' => $gciPartId],
                        ['on_hand' => 0, 'on_order' => 0, 'as_of_date' => now()->toDateString()]
                    );

                    $remainingReserved = (float) ($remainingReservedByGciPart[$gciPartId] ?? 0);
                    if ($remainingReserved > 0) {
                        $consumeReserved = min($qty, $remainingReserved);
                        $inventory->consume($consumeReserved);
                        $remainingReservedByGciPart[$gciPartId] = max(0, $remainingReserved - $consumeReserved);
                    }
                }

                $postedIssueLines[] = [
                    'tag_number' => $tagNo,
                    'part_no' => $partNo,
                    'qty' => $qty,
                    'gci_part_id' => $gciPartId > 0 ? $gciPartId : null,
                    'part_id' => $partId > 0 ? $partId : null,
                    'location_code' => $locationCode,
                    'posted_at' => now()->toDateTimeString(),
                    'traceability' => $traceability,
                ];
            }

            $order->update([
                'material_issue_lines' => $postedIssueLines,
                'material_issued_at' => now(),
                'material_issued_by' => auth()->id() ?? 1,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'data' => $this->buildSupplyStatus($order->fresh()),
        ]);
    }
}
