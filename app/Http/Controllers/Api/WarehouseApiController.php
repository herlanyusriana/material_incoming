<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\Bom;
use App\Models\Receive;
use App\Models\LocationInventory;
use App\Models\GciInventory;
use App\Services\ProductionInventoryFlowService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class WarehouseApiController extends Controller
{
    private const STAGING_LOCATION_CODE = 'AA-BULK';

    private function inventoryFlowService(): ProductionInventoryFlowService
    {
        return app(ProductionInventoryFlowService::class);
    }

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

    private function targetSupplyDate(Request $request): string
    {
        $selectedDate = trim((string) $request->query('date', $request->input('date', '')));

        return $selectedDate !== ''
            ? Carbon::parse($selectedDate)->toDateString()
            : now()->toDateString();
    }

    private function supplyAllOrders(string $targetDate)
    {
        return ProductionOrder::query()
            ->with('part')
            ->whereDate('plan_date', $targetDate)
            ->whereIn('status', ['planned', 'kanban_released', 'material_hold', 'resource_hold', 'released'])
            ->whereNull('material_issued_at')
            ->whereNull('material_handed_over_at')
            ->orderBy('plan_date')
            ->orderBy('id')
            ->get();
    }

    private function issueKeys(?int $gciPartId, ?int $partId, ?string $partNo): array
    {
        $keys = [];
        $normalizedPartNo = strtoupper(trim((string) $partNo));

        if ((int) $gciPartId > 0) {
            $keys[] = 'gci:' . (int) $gciPartId;
        }

        if ((int) $partId > 0) {
            $keys[] = 'incoming:' . (int) $partId;
        }

        if ($normalizedPartNo !== '') {
            $keys[] = 'part:' . $normalizedPartNo;
        }

        return array_values(array_unique($keys));
    }

    private function requirementKeys(array $line): array
    {
        $keys = $this->issueKeys(
            (int) ($line['component_gci_part_id'] ?? 0),
            null,
            (string) ($line['component_part_no'] ?? '')
        );

        foreach (($line['allocations'] ?? []) as $allocation) {
            $keys = array_merge($keys, $this->issueKeys(
                null,
                (int) ($allocation['part_id'] ?? 0),
                (string) ($allocation['part_no'] ?? '')
            ));
        }

        return array_values(array_unique($keys));
    }

    private function scanOptionsForRequirement(array $line): array
    {
        $options = collect($line['allocations'] ?? [])->map(function (array $allocation) {
            return [
                'source_type' => (string) ($allocation['source_type'] ?? 'primary'),
                'part_id' => (int) ($allocation['part_id'] ?? 0),
                'part_no' => (string) ($allocation['part_no'] ?? ''),
                'part_name' => (string) ($allocation['part_name'] ?? ''),
                'location_code' => (string) ($allocation['location_code'] ?? ''),
                'batch_no' => (string) ($allocation['batch_no'] ?? ''),
                'qty_on_hand' => round((float) ($allocation['qty_on_hand'] ?? 0), 4),
                'request_qty' => round((float) ($allocation['request_qty'] ?? 0), 4),
            ];
        })
            ->filter(fn (array $option) => trim($option['part_no']) !== '')
            ->unique(fn (array $option) => strtoupper($option['source_type'] . '|' . $option['part_id'] . '|' . $option['part_no'] . '|' . $option['location_code'] . '|' . $option['batch_no']))
            ->values();

        if ($options->isEmpty()) {
            $options->push([
                'source_type' => 'component',
                'part_id' => 0,
                'part_no' => (string) ($line['component_part_no'] ?? 'Unknown'),
                'part_name' => (string) ($line['component_part_name'] ?? ''),
                'location_code' => '',
                'batch_no' => '',
                'qty_on_hand' => 0.0,
                'request_qty' => round((float) ($line['required_qty'] ?? 0), 4),
            ]);
        }

        return $options->all();
    }

    private function preferredScanOption(array $scanOptions): array
    {
        return collect($scanOptions)->firstWhere('source_type', 'primary')
            ?? collect($scanOptions)->first()
            ?? [];
    }

    private function mergeScanOptions(array $existing, array $incoming): array
    {
        return collect(array_merge($existing, $incoming))
            ->filter(fn (array $option) => trim((string) ($option['part_no'] ?? '')) !== '')
            ->unique(fn (array $option) => strtoupper(($option['source_type'] ?? '') . '|' . ($option['part_id'] ?? 0) . '|' . ($option['part_no'] ?? '') . '|' . ($option['location_code'] ?? '') . '|' . ($option['batch_no'] ?? '')))
            ->values()
            ->all();
    }

    private function scannedQtyForRequirement(ProductionOrder $order, array $requirementKeys): array
    {
        return collect($order->material_issue_lines ?? [])->reduce(function (array $carry, array $issueLine) use ($requirementKeys) {
            $lineKeys = $this->issueKeys(
                (int) ($issueLine['gci_part_id'] ?? 0),
                (int) ($issueLine['part_id'] ?? 0),
                (string) ($issueLine['part_no'] ?? '')
            );

            if (!empty(array_intersect($lineKeys, $requirementKeys))) {
                $carry['qty'] += (float) ($issueLine['qty'] ?? $issueLine['issued_qty'] ?? 0);
                $carry['tags']++;
            }

            return $carry;
        }, ['qty' => 0.0, 'tags' => 0]);
    }

    private function buildSupplyAllRows($orders): array
    {
        $rows = [];

        foreach ($orders as $order) {
            foreach (($order->material_request_lines ?? []) as $line) {
                $componentGciPartId = (int) ($line['component_gci_part_id'] ?? 0);
                $componentPartNo = strtoupper(trim((string) ($line['component_part_no'] ?? '')));

                if ($componentGciPartId <= 0 && $componentPartNo === '') {
                    continue;
                }

                $keys = $this->requirementKeys($line);
                $scanProgress = $this->scannedQtyForRequirement($order, $keys);
                $requiredQty = round((float) ($line['required_qty'] ?? 0), 4);
                $scannedQty = round((float) ($scanProgress['qty'] ?? 0), 4);
                $remainingQty = max(0, round($requiredQty - $scannedQty, 4));

                if ($remainingQty <= 0) {
                    continue;
                }

                $groupKey = $componentGciPartId > 0
                    ? 'gci:' . $componentGciPartId
                    : 'part:' . $componentPartNo;
                $scanOptions = $this->scanOptionsForRequirement($line);
                $preferredOption = $this->preferredScanOption($scanOptions);

                if (!isset($rows[$groupKey])) {
                    $rows[$groupKey] = [
                        'group_key' => $groupKey,
                        'gci_part_id' => $componentGciPartId,
                        'part_no' => (string) ($line['component_part_no'] ?? 'Unknown'),
                        'part_name' => (string) ($line['component_part_name'] ?? ''),
                        'scan_part_no' => (string) ($preferredOption['part_no'] ?? $line['component_part_no'] ?? 'Unknown'),
                        'scan_part_name' => (string) ($preferredOption['part_name'] ?? $line['component_part_name'] ?? ''),
                        'scan_options' => [],
                        'accepted_part_nos' => [],
                        'uom' => (string) ($line['uom'] ?? 'PCS'),
                        'total_required_qty' => 0.0,
                        'total_scanned_qty' => 0.0,
                        'total_remaining_qty' => 0.0,
                        'wo_count' => 0,
                        'orders' => [],
                    ];
                }

                $rows[$groupKey]['scan_options'] = $this->mergeScanOptions($rows[$groupKey]['scan_options'], $scanOptions);
                $rows[$groupKey]['accepted_part_nos'] = collect($rows[$groupKey]['scan_options'])
                    ->pluck('part_no')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                $rows[$groupKey]['total_required_qty'] = round($rows[$groupKey]['total_required_qty'] + $requiredQty, 4);
                $rows[$groupKey]['total_scanned_qty'] = round($rows[$groupKey]['total_scanned_qty'] + $scannedQty, 4);
                $rows[$groupKey]['total_remaining_qty'] = round($rows[$groupKey]['total_remaining_qty'] + $remainingQty, 4);
                $rows[$groupKey]['wo_count']++;
                $rows[$groupKey]['orders'][] = [
                    'id' => (int) $order->id,
                    'wo_number' => (string) ($order->production_order_number ?? $order->transaction_no ?? $order->id),
                    'fg_part_no' => (string) ($order->part?->part_no ?? ''),
                    'fg_part_name' => (string) ($order->part?->part_name ?? ''),
                    'required_qty' => $requiredQty,
                    'scanned_qty' => $scannedQty,
                    'remaining_qty' => $remainingQty,
                    'scan_tags' => (int) ($scanProgress['tags'] ?? 0),
                    'scan_options' => $scanOptions,
                ];
            }
        }

        return collect($rows)
            ->sortBy([
                fn (array $row) => $row['part_no'],
                fn (array $row) => $row['scan_part_no'],
            ])
            ->values()
            ->all();
    }

    public function supplyAllMaterials(Request $request)
    {
        $targetDate = $this->targetSupplyDate($request);
        $orders = $this->supplyAllOrders($targetDate);

        return response()->json([
            'status' => 'success',
            'meta' => [
                'date' => $targetDate,
                'wo_count' => $orders->count(),
            ],
            'data' => $this->buildSupplyAllRows($orders),
        ]);
    }

    private function resolveScannedTag(string $rawTag): array
    {
        $tagNo = trim($rawTag);
        $filterPartNo = null;
        $receiveId = null;

        if (str_starts_with($tagNo, '{') && str_ends_with($tagNo, '}')) {
            $decoded = json_decode($tagNo, true);
            if (is_array($decoded)) {
                if (!empty($decoded['tag'])) {
                    $tagNo = (string) $decoded['tag'];
                } elseif (!empty($decoded['barcode'])) {
                    $tagNo = (string) $decoded['barcode'];
                } elseif (!empty($decoded['part_no'])) {
                    $tagNo = (string) $decoded['part_no'];
                }

                if (!empty($decoded['part_no'])) {
                    $filterPartNo = strtoupper(trim((string) $decoded['part_no']));
                }

                if (!empty($decoded['receive_id'])) {
                    $receiveId = (int) $decoded['receive_id'];
                }
            }
        }

        $tagNo = strtoupper(trim($tagNo));
        $locInv = LocationInventory::where('batch_no', $tagNo)
            ->where('qty_on_hand', '>', 0)
            ->orderByRaw('CASE WHEN location_code = ? THEN 1 ELSE 0 END', [self::STAGING_LOCATION_CODE])
            ->with('part', 'gciPart')
            ->first();

        if ($locInv) {
            return [
                'tag_no' => $tagNo,
                'gci_part_id' => (int) ($locInv->gci_part_id ?? 0),
                'part_id' => (int) ($locInv->part_id ?? 0),
                'qty_available' => (float) $locInv->qty_on_hand,
                'part_no' => (string) ($locInv->part?->part_no ?? $locInv->gciPart?->part_no ?? 'Unknown'),
                'part_name' => (string) ($locInv->part?->part_name_gci ?? $locInv->part?->part_name_vendor ?? $locInv->gciPart?->part_name ?? ''),
                'location_code' => (string) $locInv->location_code,
                'source_location_code' => (string) $locInv->location_code,
                'loc_inv' => $locInv,
                'traceability' => [],
            ];
        }

        $receiveQuery = Receive::query()
            ->with('arrivalItem.part', 'arrivalItem.gciPartVendor')
            ->where(function ($query) use ($tagNo, $receiveId) {
                $query->where('tag', $tagNo);

                if ($receiveId > 0) {
                    $query->orWhere('id', $receiveId);
                }

                if (ctype_digit($tagNo)) {
                    $query->orWhere('id', (int) $tagNo);
                }
            });

        if ($filterPartNo) {
            $receiveQuery->whereHas('arrivalItem.part', fn ($query) => $query->where('part_no', $filterPartNo));
        }

        $receive = $receiveQuery->first();
        if (!$receive) {
            return [];
        }

        $arrItem = $receive->arrivalItem;
        $partNo = $arrItem?->part?->part_no
            ?? $arrItem?->gciPartVendor?->vendor_part_no
            ?? 'Unknown';

        return [
            'tag_no' => $tagNo,
            'gci_part_id' => (int) ($arrItem?->part?->gci_part_id ?? $arrItem?->gciPartVendor?->gci_part_id ?? 0),
            'part_id' => (int) ($arrItem?->part?->id ?? 0),
            'qty_available' => (float) ($receive->qty ?? 0),
            'part_no' => (string) $partNo,
            'part_name' => (string) ($arrItem?->part?->part_name_gci ?? $arrItem?->part?->part_name_vendor ?? ''),
            'location_code' => null,
            'source_location_code' => strtoupper(trim((string) ($receive->location_code ?? ''))),
            'loc_inv' => null,
            'traceability' => [
                'source_receive_id' => (int) $receive->id,
                'source_arrival_id' => (int) ($arrItem?->arrival_id ?? 0),
                'source_invoice_no' => (string) ($receive->invoice_no ?? ''),
                'source_delivery_note_no' => (string) ($receive->delivery_note_no ?? ''),
                'source_tag' => $tagNo,
            ],
        ];
    }

    public function scanSupplyAll(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'tag_no' => 'required|string',
            'issue_qty' => 'nullable|numeric|min:0.0001',
        ]);

        $targetDate = $this->targetSupplyDate($request);
        $tag = $this->resolveScannedTag((string) $validated['tag_no']);

        if (empty($tag) || (int) ($tag['gci_part_id'] ?? 0) <= 0 || (float) ($tag['qty_available'] ?? 0) <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Label/Tag tidak dikenali atau qty kosong. Pastikan ini Label RM GCI.',
            ], 422);
        }

        $orders = $this->supplyAllOrders($targetDate);
        $matches = [];
        $tagKeys = $this->issueKeys((int) $tag['gci_part_id'], (int) $tag['part_id'], (string) $tag['part_no']);

        foreach ($orders as $order) {
            foreach (($order->material_request_lines ?? []) as $line) {
                $requirementKeys = $this->requirementKeys($line);
                if (empty(array_intersect($tagKeys, $requirementKeys))) {
                    continue;
                }

                $alreadyScannedSameTag = collect($order->material_issue_lines ?? [])
                    ->contains(fn (array $issueLine) => strtoupper((string) ($issueLine['tag_number'] ?? '')) === $tag['tag_no']);

                if ($alreadyScannedSameTag) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Tag {$tag['tag_no']} sudah pernah discan untuk salah satu WO pada tanggal ini.",
                    ], 422);
                }

                $progress = $this->scannedQtyForRequirement($order, $requirementKeys);
                $requiredQty = (float) ($line['required_qty'] ?? 0);
                $remainingQty = max(0, round($requiredQty - (float) ($progress['qty'] ?? 0), 4));

                if ($remainingQty <= 0) {
                    continue;
                }

                $matches[] = [
                    'order' => $order,
                    'line' => $line,
                    'remaining_qty' => $remainingQty,
                ];
            }
        }

        if (empty($matches)) {
            return response()->json([
                'status' => 'error',
                'message' => "Material {$tag['part_no']} tidak ada atau sudah terpenuhi pada WO tanggal {$targetDate}.",
            ], 422);
        }

        $totalRemaining = round(collect($matches)->sum('remaining_qty'), 4);
        $qtyAvailable = round((float) $tag['qty_available'], 4);
        $issueQty = array_key_exists('issue_qty', $validated)
            ? round((float) $validated['issue_qty'], 4)
            : null;

        if ($issueQty !== null && $issueQty > $qtyAvailable) {
            return response()->json([
                'status' => 'error',
                'message' => "Qty issue {$issueQty} melebihi qty tag {$qtyAvailable}.",
            ], 422);
        }

        if ($issueQty === null && $qtyAvailable > $totalRemaining) {
            return response()->json([
                'status' => 'needs_qty_confirmation',
                'message' => 'Qty tag lebih besar dari total sisa kebutuhan WO. Pilih qty yang mau disupply.',
                'data' => [
                    'tag_no' => $tag['tag_no'],
                    'part_no' => $tag['part_no'],
                    'qty_available' => $qtyAvailable,
                    'remaining_requirement_qty' => $totalRemaining,
                    'suggested_qty' => $totalRemaining,
                    'source_location_code' => $tag['source_location_code'],
                    'target_date' => $targetDate,
                ],
            ], 409);
        }

        $issueQty ??= min($qtyAvailable, $totalRemaining);
        $remainingTagQty = max(0, round($qtyAvailable - $issueQty, 4));

        if (!$tag['loc_inv'] && $remainingTagQty > 0 && empty($tag['source_location_code'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tag ini discan partial, tapi lokasi receive kosong. Isi lokasi receive dulu supaya sisa tag tetap termonitor.',
            ], 422);
        }

        $allocations = [];
        $qtyToAllocate = $issueQty;

        foreach ($matches as $match) {
            if ($qtyToAllocate <= 0) {
                break;
            }

            $allocatedQty = min($qtyToAllocate, (float) $match['remaining_qty']);
            $allocations[] = [
                'order' => $match['order'],
                'line' => $match['line'],
                'qty' => round($allocatedQty, 4),
            ];
            $qtyToAllocate = round($qtyToAllocate - $allocatedQty, 4);
        }

        $stagedToAa = false;
        $locationCode = $tag['location_code'] ?: self::STAGING_LOCATION_CODE;
        $traceability = is_array($tag['traceability'] ?? null) ? $tag['traceability'] : [];

        DB::transaction(function () use ($tag, $issueQty, $remainingTagQty, $allocations, &$stagedToAa, &$locationCode, $traceability) {
            $sourceReference = 'PROD_SUPPLY_ALL#' . now()->format('YmdHis');
            $stagingLocation = self::STAGING_LOCATION_CODE;
            $locInv = $tag['loc_inv'];

            if ($locInv && strtoupper(trim((string) $locInv->location_code)) !== $stagingLocation) {
                LocationInventory::consumeStock(
                    (int) $tag['part_id'] > 0 ? (int) $tag['part_id'] : null,
                    (string) $locInv->location_code,
                    $issueQty,
                    (string) $tag['tag_no'],
                    (int) $tag['gci_part_id'] > 0 ? (int) $tag['gci_part_id'] : null,
                    'PRODUCTION_STAGE_AA_OUT',
                    $sourceReference,
                    array_merge(['source_tag' => $tag['tag_no']], $traceability)
                );

                LocationInventory::updateStock(
                    (int) $tag['part_id'] > 0 ? (int) $tag['part_id'] : null,
                    $stagingLocation,
                    $issueQty,
                    (string) $tag['tag_no'],
                    null,
                    (int) $tag['gci_part_id'] > 0 ? (int) $tag['gci_part_id'] : null,
                    'PRODUCTION_STAGE_AA_IN',
                    $sourceReference,
                    array_merge(['source_tag' => $tag['tag_no']], $traceability)
                );

                $locationCode = $stagingLocation;
                $stagedToAa = true;
            } elseif (!$locInv) {
                if ($remainingTagQty > 0 && !empty($tag['source_location_code'])) {
                    LocationInventory::updateStock(
                        (int) $tag['part_id'] > 0 ? (int) $tag['part_id'] : null,
                        (string) $tag['source_location_code'],
                        $remainingTagQty,
                        (string) $tag['tag_no'],
                        null,
                        (int) $tag['gci_part_id'] > 0 ? (int) $tag['gci_part_id'] : null,
                        'RECEIVE_PARTIAL_REMAINING_IN',
                        $sourceReference,
                        array_merge(['source_tag' => $tag['tag_no']], $traceability)
                    );
                }

                LocationInventory::updateStock(
                    (int) $tag['part_id'] > 0 ? (int) $tag['part_id'] : null,
                    $stagingLocation,
                    $issueQty,
                    (string) $tag['tag_no'],
                    null,
                    (int) $tag['gci_part_id'] > 0 ? (int) $tag['gci_part_id'] : null,
                    'PRODUCTION_STAGE_AA_IN',
                    $sourceReference,
                    array_merge(['source_tag' => $tag['tag_no']], $traceability)
                );

                $locationCode = $stagingLocation;
                $stagedToAa = true;
            }

            foreach ($allocations as $allocation) {
                /** @var ProductionOrder $order */
                $order = $allocation['order'];
                $line = $allocation['line'];
                $tags = $order->material_issue_lines ?? [];
                $consumptionPolicy = (string) ($line['consumption_policy'] ?? (($line['is_backflush'] ?? true) ? 'backflush_return' : 'direct_issue'));
                $isBackflush = (bool) ($line['is_backflush'] ?? $consumptionPolicy !== 'direct_issue');

                $tags[] = [
                    'tag_number' => (string) $tag['tag_no'],
                    'part_no' => (string) $tag['part_no'],
                    'part_name' => (string) $tag['part_name'],
                    'qty' => (float) $allocation['qty'],
                    'tag_qty' => (float) $tag['qty_available'],
                    'remaining_qty_after_issue' => $remainingTagQty,
                    'is_partial_issue' => $issueQty < round((float) $tag['qty_available'], 4),
                    'consumption_policy' => $consumptionPolicy,
                    'is_backflush' => $isBackflush,
                    'backflushed_qty' => 0,
                    'gci_part_id' => (int) $tag['gci_part_id'],
                    'part_id' => (int) $tag['part_id'],
                    'location_code' => $locationCode,
                    'source_location_code' => $tag['source_location_code'],
                    'staged_to_aa' => $stagedToAa,
                    'traceability' => array_merge(['source_tag' => $tag['tag_no'], 'supply_all' => true], $traceability),
                    'scanned_at' => now()->toDateTimeString(),
                ];

                $order->update(['material_issue_lines' => array_values($tags)]);
            }
        });

        $orders = $this->supplyAllOrders($targetDate);

        return response()->json([
            'status' => 'success',
            'message' => "Tag {$tag['tag_no']} dialokasikan ke " . count($allocations) . ' WO.',
            'data' => [
                'tag_no' => $tag['tag_no'],
                'part_no' => $tag['part_no'],
                'issue_qty' => $issueQty,
                'allocations' => collect($allocations)->map(fn ($allocation) => [
                    'wo_id' => (int) $allocation['order']->id,
                    'wo_number' => (string) ($allocation['order']->production_order_number ?? $allocation['order']->transaction_no ?? $allocation['order']->id),
                    'qty' => (float) $allocation['qty'],
                ])->values(),
                'materials' => $this->buildSupplyAllRows($orders),
            ],
        ]);
    }

    public function postSupplyAll(Request $request)
    {
        $targetDate = $this->targetSupplyDate($request);
        $orders = $this->supplyAllOrders($targetDate)
            ->filter(fn (ProductionOrder $order) => !empty($order->material_issue_lines))
            ->values();

        if ($orders->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Belum ada tag yang discan untuk WO tanggal ini.',
            ], 422);
        }

        $posted = [];

        foreach ($orders as $order) {
            $response = $this->postSupply($request, $order->id);
            if ($response->getStatusCode() >= 400) {
                $payload = $response->getData(true);

                return response()->json([
                    'status' => 'error',
                    'message' => ($payload['message'] ?? 'Gagal posting salah satu WO') . ' WO: ' . ($order->production_order_number ?? $order->id),
                    'posted_before_error' => $posted,
                ], $response->getStatusCode());
            }

            $posted[] = [
                'id' => (int) $order->id,
                'wo_number' => (string) ($order->production_order_number ?? $order->transaction_no ?? $order->id),
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => count($posted) . ' WO berhasil diposting supply WH.',
            'data' => [
                'date' => $targetDate,
                'posted_orders' => $posted,
            ],
        ]);
    }

    private function resolveRequirementBalance(ProductionOrder $order, ?int $gciPartId, ?int $partId, string $partNo): ?array
    {
        $issueKeys = $this->issueKeys($gciPartId, $partId, $partNo);

        if (empty($issueKeys) || empty($order->material_request_lines)) {
            return null;
        }

        $issueLines = collect($order->material_issue_lines ?? []);

        foreach (($order->material_request_lines ?? []) as $line) {
            $requirementKeys = $this->requirementKeys($line);

            if (empty(array_intersect($issueKeys, $requirementKeys))) {
                continue;
            }

            $scannedQty = $issueLines->sum(function (array $issueLine) use ($requirementKeys) {
                $lineKeys = $this->issueKeys(
                    (int) ($issueLine['gci_part_id'] ?? 0),
                    (int) ($issueLine['part_id'] ?? 0),
                    (string) ($issueLine['part_no'] ?? '')
                );

                return empty(array_intersect($lineKeys, $requirementKeys))
                    ? 0
                    : (float) ($issueLine['qty'] ?? $issueLine['issued_qty'] ?? 0);
            });

            $requiredQty = (float) ($line['required_qty'] ?? 0);

            return [
                'component_part_no' => (string) ($line['component_part_no'] ?? ''),
                'component_part_name' => (string) ($line['component_part_name'] ?? ''),
                'consumption_policy' => (string) ($line['consumption_policy'] ?? (($line['is_backflush'] ?? true) ? 'backflush_return' : 'direct_issue')),
                'policy_source' => (string) ($line['policy_source'] ?? 'legacy_default'),
                'is_backflush' => (bool) ($line['is_backflush'] ?? true),
                'required_qty' => $requiredQty,
                'scanned_qty' => round($scannedQty, 4),
                'remaining_qty' => max(0, round($requiredQty - $scannedQty, 4)),
            ];
        }

        return null;
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
                $partId = (int) ($line['part_id'] ?? 0);
                $partNo = strtoupper(trim((string) ($line['part_no'] ?? '')));
                $keys = [];
                if ($gciPartId > 0) {
                    $keys[] = 'gci:' . $gciPartId;
                }
                if ($partId > 0) {
                    $keys[] = 'incoming:' . $partId;
                }
                if ($partNo !== '') {
                    $keys[] = 'part:' . $partNo;
                }

                $keys = array_values(array_unique($keys));

                foreach ($keys as $key) {
                    if (!isset($carry[$key])) {
                        $carry[$key] = [
                            'scanned_qty' => 0.0,
                            'scanned_tags' => 0,
                        ];
                    }

                    $carry[$key]['scanned_qty'] += (float) ($line['qty'] ?? $line['issued_qty'] ?? 0);
                    $carry[$key]['scanned_tags']++;
                }

                return $carry;
            }, []);
        
        $requirements = [];

        if (!empty($order->material_request_lines)) {
            foreach (($order->material_request_lines ?? []) as $line) {
                $allocations = array_values($line['allocations'] ?? []);
                $primaryAllocation = $allocations[0] ?? null;
                $requirements[] = [
                    'gci_part_id' => (int) ($line['component_gci_part_id'] ?? 0),
                    'part_no' => (string) ($line['component_part_no'] ?? 'Unknown'),
                    'part_name' => (string) ($line['component_part_name'] ?? ''),
                    'component_part_no' => (string) ($line['component_part_no'] ?? 'Unknown'),
                    'component_part_name' => (string) ($line['component_part_name'] ?? ''),
                    'scan_part_no' => (string) ($primaryAllocation['part_no'] ?? $line['component_part_no'] ?? 'Unknown'),
                    'scan_part_name' => (string) ($primaryAllocation['part_name'] ?? $line['component_part_name'] ?? ''),
                    'uom' => (string) ($line['uom'] ?? 'PCS'),
                    'consumption_policy' => (string) ($line['consumption_policy'] ?? (($line['is_backflush'] ?? true) ? 'backflush_return' : 'direct_issue')),
                    'policy_source' => (string) ($line['policy_source'] ?? 'legacy_default'),
                    'is_backflush' => (bool) ($line['is_backflush'] ?? true),
                    'total_qty' => (float) ($line['required_qty'] ?? 0),
                    'allocated_qty' => (float) ($line['available_qty'] ?? 0),
                    'shortage_qty' => (float) ($line['shortage_qty'] ?? 0),
                    'allocations' => $allocations,
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
                        'component_part_no' => (string) ($req['part']?->part_no ?? $req['part_no'] ?? 'Unknown'),
                        'component_part_name' => (string) ($req['part']?->part_name ?? ''),
                        'scan_part_no' => (string) ($req['part']?->part_no ?? $req['part_no'] ?? 'Unknown'),
                        'scan_part_name' => (string) ($req['part']?->part_name ?? ''),
                        'uom' => (string) ($req['uom'] ?? 'PCS'),
                        'consumption_policy' => 'backflush_return',
                        'policy_source' => 'bom_fallback',
                        'is_backflush' => true,
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
                $keys = [];
                if ($gciPartId > 0) {
                    $keys[] = 'gci:' . $gciPartId;
                }
                if ($partNo !== '') {
                    $keys[] = 'part:' . $partNo;
                }
                foreach (($requirement['allocations'] ?? []) as $allocation) {
                    $allocationPartId = (int) ($allocation['part_id'] ?? 0);
                    $allocationPartNo = strtoupper(trim((string) ($allocation['part_no'] ?? '')));
                    if ($allocationPartId > 0) {
                        $keys[] = 'incoming:' . $allocationPartId;
                    }
                    if ($allocationPartNo !== '') {
                        $keys[] = 'part:' . $allocationPartNo;
                    }
                }

                $keys = array_values(array_unique($keys));
                $progress = collect($keys)->reduce(function (array $carry, string $key) use ($scanProgress) {
                    $carry['scanned_qty'] += (float) ($scanProgress[$key]['scanned_qty'] ?? 0);
                    $carry['scanned_tags'] += (int) ($scanProgress[$key]['scanned_tags'] ?? 0);
                    return $carry;
                }, ['scanned_qty' => 0.0, 'scanned_tags' => 0]);
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
                'inventory_flow' => $this->inventoryFlowService()->summarizeOrderFlow($order),
            ]
        ]);
    }

    public function scanTag(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);
        $validated = $request->validate([
            'tag_no' => 'required|string',
            'issue_qty' => 'nullable|numeric|min:0.0001',
        ]);

        $tags = $order->material_issue_lines ?? [];
        $tagNo = trim((string) $validated['tag_no']);
        $filterPartNo = null;
        $receiveId = null;

        if (str_starts_with($tagNo, '{') && str_ends_with($tagNo, '}')) {
            $decoded = json_decode($tagNo, true);
            if (is_array($decoded)) {
                if (!empty($decoded['tag'])) {
                    $tagNo = (string) $decoded['tag'];
                } elseif (!empty($decoded['barcode'])) {
                    $tagNo = (string) $decoded['barcode'];
                } elseif (!empty($decoded['part_no'])) {
                    $tagNo = (string) $decoded['part_no'];
                }

                if (!empty($decoded['part_no'])) {
                    $filterPartNo = strtoupper(trim((string) $decoded['part_no']));
                }

                if (!empty($decoded['receive_id'])) {
                    $receiveId = (int) $decoded['receive_id'];
                }
            }
        }

        $tagNo = strtoupper(trim($tagNo));

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
        $sourceLocationCode = null;
        $stagedToAa = false;
        $receive = null;

        // 1. Cek apakah barang sudah masuk rak (Location Inventory)
        $locInv = LocationInventory::where('batch_no', $tagNo)
            ->where('qty_on_hand', '>', 0)
            ->orderByRaw('CASE WHEN location_code = ? THEN 1 ELSE 0 END', [self::STAGING_LOCATION_CODE])
            ->with('part', 'gciPart')
            ->first();
        if ($locInv) {
            $gciPartId = $locInv->gci_part_id;
            $partId = $locInv->part_id;
            $qtyAvailable = $locInv->qty_on_hand;
            $partNo = $locInv->part?->part_no ?? $locInv->gciPart?->part_no ?? 'Unknown';
            $locationCode = $locInv->location_code;
            $sourceLocationCode = $locInv->location_code;
        } else {
            $receiveQuery = Receive::query()
                ->with('arrivalItem.part', 'arrivalItem.gciPartVendor')
                ->where(function ($query) use ($tagNo, $receiveId) {
                    $query->where('tag', $tagNo);

                    if ($receiveId > 0) {
                        $query->orWhere('id', $receiveId);
                    }

                    if (ctype_digit($tagNo)) {
                        $query->orWhere('id', (int) $tagNo);
                    }
                });
            if ($filterPartNo) {
                $receiveQuery->whereHas('arrivalItem.part', fn($query) => $query->where('part_no', $filterPartNo));
            }
            $receive = $receiveQuery->first();
            if ($receive) {
                $arrItem = $receive->arrivalItem;
                $partNo = $arrItem?->part?->part_no
                    ?? $arrItem?->gciPartVendor?->vendor_part_no
                    ?? 'Unknown';
                $gciPartId = (int) ($arrItem?->part?->gci_part_id ?? $arrItem?->gciPartVendor?->gci_part_id ?? 0);
                $partId = (int) ($arrItem?->part?->id ?? 0);
                $qtyAvailable = (float) ($receive->qty ?? 0);
                $sourceLocationCode = strtoupper(trim((string) ($receive->location_code ?? '')));
                $traceability = [
                    'source_receive_id' => (int) $receive->id,
                    'source_arrival_id' => (int) ($arrItem?->arrival_id ?? 0),
                    'source_invoice_no' => (string) ($receive->invoice_no ?? ''),
                    'source_delivery_note_no' => (string) ($receive->delivery_note_no ?? ''),
                    'source_tag' => $tagNo,
                ];
            }
        }

        if (!$gciPartId || $qtyAvailable <= 0) {
            return response()->json(['status' => 'error', 'message' => "Label/Tag tidak dikenali atau qty kosong. Pastikan ini Label RM GCI!"], 422);
        }

        $materialSesuaiBom = false;

        if (!empty($order->material_request_lines)) {
            foreach (($order->material_request_lines ?? []) as $line) {
                $componentGciPartId = (int) ($line['component_gci_part_id'] ?? 0);
                $componentPartNo = strtoupper(trim((string) ($line['component_part_no'] ?? '')));
                $allocations = collect($line['allocations'] ?? []);

                $matchesAllocation = $allocations->contains(function ($allocation) use ($partId, $partNo) {
                    $allocationPartId = (int) ($allocation['part_id'] ?? 0);
                    $allocationPartNo = strtoupper(trim((string) ($allocation['part_no'] ?? '')));

                    return ($partId > 0 && $allocationPartId === $partId)
                        || ($allocationPartNo !== '' && $allocationPartNo === strtoupper(trim($partNo)));
                });

                if (
                    ($componentGciPartId > 0 && $componentGciPartId === $gciPartId)
                    || ($componentPartNo !== '' && $componentPartNo === strtoupper(trim($partNo)))
                    || $matchesAllocation
                ) {
                    $materialSesuaiBom = true;
                    break;
                }
            }
        } else {
            // Fallback ke BOM langsung kalau request line belum terbentuk.
            $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
            if (!$bom) {
                return response()->json(['status' => 'error', 'message' => "Mesin ini tidak memiliki BOM aktif!"], 422);
            }

            $reqs = $bom->getTotalMaterialRequirements($order->qty_planned);

            foreach ($reqs as $req) {
                if ($this->isWarehouseScannableRm((string) ($req['make_or_buy'] ?? ''))
                    && $req['part']?->id === $gciPartId) {
                    $materialSesuaiBom = true;
                    break;
                }
            }
        }

        if (!$materialSesuaiBom) {
            return response()->json(['status' => 'error', 'message' => "ERROR: Material {$partNo} TIDAK ADA dalam resep BOM mesin ini!"], 422);
        }

        $requirementBalance = $this->resolveRequirementBalance($order, $gciPartId, $partId, $partNo);
        $remainingRequirementQty = (float) ($requirementBalance['remaining_qty'] ?? $qtyAvailable);

        if ($requirementBalance && $remainingRequirementQty <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Requirement {$partNo} sudah terpenuhi untuk WO ini.",
            ], 422);
        }

        $issueQty = array_key_exists('issue_qty', $validated)
            ? round((float) $validated['issue_qty'], 4)
            : null;

        if ($issueQty !== null && $issueQty > round((float) $qtyAvailable, 4)) {
            return response()->json([
                'status' => 'error',
                'message' => "Qty issue {$issueQty} melebihi qty tag {$qtyAvailable}.",
            ], 422);
        }

        if ($issueQty === null && $requirementBalance && round((float) $qtyAvailable, 4) > $remainingRequirementQty) {
            return response()->json([
                'status' => 'needs_qty_confirmation',
                'message' => 'Qty tag lebih besar dari sisa requirement WO. Pilih qty yang mau disupply.',
                'data' => [
                    'tag_no' => $tagNo,
                    'part_no' => $partNo,
                    'qty_available' => round((float) $qtyAvailable, 4),
                    'remaining_requirement_qty' => $remainingRequirementQty,
                    'suggested_qty' => $remainingRequirementQty,
                    'component_part_no' => $requirementBalance['component_part_no'] ?? '',
                    'component_part_name' => $requirementBalance['component_part_name'] ?? '',
                    'location_code' => $locationCode,
                    'source_location_code' => $sourceLocationCode,
                ],
            ], 409);
        }

        $issueQty ??= round((float) $qtyAvailable, 4);
        $remainingTagQty = max(0, round((float) $qtyAvailable - $issueQty, 4));

        if (!$locInv && $remainingTagQty > 0 && empty($sourceLocationCode)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tag ini discan partial, tapi lokasi receive kosong. Isi lokasi receive dulu supaya sisa tag tetap termonitor.',
            ], 422);
        }

        $consumptionPolicy = (string) ($requirementBalance['consumption_policy'] ?? 'backflush_return');
        $isBackflush = (bool) ($requirementBalance['is_backflush'] ?? $consumptionPolicy !== 'direct_issue');

        DB::transaction(function () use (
            &$tags,
            $order,
            $tagNo,
            $partNo,
            $qtyAvailable,
            $issueQty,
            $remainingTagQty,
            $gciPartId,
            $partId,
            &$locationCode,
            $sourceLocationCode,
            &$traceability,
            &$stagedToAa,
            $locInv,
            $consumptionPolicy,
            $isBackflush
        ) {
            $sourceReference = 'PROD#' . ($order->production_order_number ?: $order->id);
            $stagingLocation = self::STAGING_LOCATION_CODE;

            if ($locInv && strtoupper(trim((string) $locInv->location_code)) !== $stagingLocation) {
                LocationInventory::consumeStock(
                    $partId > 0 ? $partId : null,
                    (string) $locInv->location_code,
                    $issueQty,
                    $tagNo,
                    $gciPartId > 0 ? $gciPartId : null,
                    'PRODUCTION_STAGE_AA_OUT',
                    $sourceReference,
                    array_merge(['source_tag' => $tagNo], $traceability)
                );

                LocationInventory::updateStock(
                    $partId > 0 ? $partId : null,
                    $stagingLocation,
                    $issueQty,
                    $tagNo,
                    null,
                    $gciPartId > 0 ? $gciPartId : null,
                    'PRODUCTION_STAGE_AA_IN',
                    $sourceReference,
                    array_merge(['source_tag' => $tagNo], $traceability)
                );

                $locationCode = $stagingLocation;
                $stagedToAa = true;
            } elseif (!$locInv) {
                if ($remainingTagQty > 0 && !empty($sourceLocationCode)) {
                    LocationInventory::updateStock(
                        $partId > 0 ? $partId : null,
                        $sourceLocationCode,
                        $remainingTagQty,
                        $tagNo,
                        null,
                        $gciPartId > 0 ? $gciPartId : null,
                        'RECEIVE_PARTIAL_REMAINING_IN',
                        $sourceReference,
                        array_merge(['source_tag' => $tagNo], $traceability)
                    );
                }

                LocationInventory::updateStock(
                    $partId > 0 ? $partId : null,
                    $stagingLocation,
                    $issueQty,
                    $tagNo,
                    null,
                    $gciPartId > 0 ? $gciPartId : null,
                    'PRODUCTION_STAGE_AA_IN',
                    $sourceReference,
                    array_merge(['source_tag' => $tagNo], $traceability)
                );

                $locationCode = $stagingLocation;
                $stagedToAa = true;
            }

            if (!$locationCode) {
                $locationCode = $stagingLocation;
            }

            $traceability = array_merge($traceability, [
                'source_tag' => $tagNo,
            ]);

            $tags[] = [
                'tag_number' => $tagNo,
                'part_no' => $partNo,
                'qty' => $issueQty,
                'tag_qty' => round((float) $qtyAvailable, 4),
                'remaining_qty_after_issue' => $remainingTagQty,
                'is_partial_issue' => $issueQty < round((float) $qtyAvailable, 4),
                'consumption_policy' => $consumptionPolicy,
                'is_backflush' => $isBackflush,
                'backflushed_qty' => 0,
                'gci_part_id' => $gciPartId,
                'part_id' => $partId,
                'location_code' => $locationCode,
                'source_location_code' => $sourceLocationCode,
                'staged_to_aa' => $stagedToAa,
                'traceability' => $traceability,
                'scanned_at' => now()->toDateTimeString(),
            ];

            $order->update(['material_issue_lines' => $tags]);
        });

        return response()->json(['status' => 'success', 'data' => $tags]);
    }

    public function deleteTag(Request $request, $id, $tagNo)
    {
        $order = ProductionOrder::findOrFail($id);
        $tags = $order->material_issue_lines ?? [];
        $removedTag = null;
        $filtered = [];

        foreach ($tags as $tag) {
            if (($tag['tag_number'] ?? '') === $tagNo && $removedTag === null) {
                $removedTag = $tag;
                continue;
            }
            $filtered[] = $tag;
        }

        if ($removedTag && ($removedTag['staged_to_aa'] ?? false) && !$order->material_issued_at) {
            DB::transaction(function () use ($order, $removedTag) {
                $qty = (float) ($removedTag['qty'] ?? 0);
                $partId = (int) ($removedTag['part_id'] ?? 0);
                $gciPartId = (int) ($removedTag['gci_part_id'] ?? 0);
                $tagNo = (string) ($removedTag['tag_number'] ?? '');
                $sourceLocationCode = strtoupper(trim((string) ($removedTag['source_location_code'] ?? '')));
                $traceability = is_array($removedTag['traceability'] ?? null) ? $removedTag['traceability'] : [];
                $sourceReference = 'PROD#' . ($order->production_order_number ?: $order->id);

                if ($qty > 0 && $tagNo !== '') {
                    LocationInventory::consumeStock(
                        $partId > 0 ? $partId : null,
                        self::STAGING_LOCATION_CODE,
                        $qty,
                        $tagNo,
                        $gciPartId > 0 ? $gciPartId : null,
                        'PRODUCTION_STAGE_AA_CANCEL_OUT',
                        $sourceReference,
                        array_merge(['source_tag' => $tagNo], $traceability)
                    );

                    if ($sourceLocationCode !== '') {
                        LocationInventory::updateStock(
                            $partId > 0 ? $partId : null,
                            $sourceLocationCode,
                            $qty,
                            $tagNo,
                            null,
                            $gciPartId > 0 ? $gciPartId : null,
                            'PRODUCTION_STAGE_AA_CANCEL_IN',
                            $sourceReference,
                            array_merge(['source_tag' => $tagNo], $traceability)
                        );
                    }
                }
            });
        }

        $order->update(['material_issue_lines' => array_values($filtered)]);

        return response()->json(['status' => 'success', 'data' => array_values($filtered)]);
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

        $flowService = $this->inventoryFlowService();

        DB::transaction(function () use ($order, $tags, &$remainingReservedByGciPart, $flowService) {
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

                $isBackflush = (bool) ($tag['is_backflush'] ?? true);

                if (!$isBackflush) {
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
                }

                if ($gciPartId > 0 && !$isBackflush) {
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

                $postedLine = [
                    'tag_number' => $tagNo,
                    'part_no' => $partNo,
                    'part_name' => $tag['part_name'] ?? null,
                    'qty' => $qty,
                    'tag_qty' => $tag['tag_qty'] ?? $qty,
                    'remaining_qty_after_issue' => $tag['remaining_qty_after_issue'] ?? 0,
                    'is_partial_issue' => $tag['is_partial_issue'] ?? false,
                    'consumption_policy' => $tag['consumption_policy'] ?? ($isBackflush ? 'backflush_return' : 'direct_issue'),
                    'is_backflush' => $isBackflush,
                    'backflushed_qty' => (float) ($tag['backflushed_qty'] ?? 0),
                    'gci_part_id' => $gciPartId > 0 ? $gciPartId : null,
                    'part_id' => $partId > 0 ? $partId : null,
                    'location_code' => $locationCode,
                    'source_location_code' => $tag['source_location_code'] ?? null,
                    'staged_to_aa' => $tag['staged_to_aa'] ?? false,
                    'posted_at' => now()->toDateTimeString(),
                    'traceability' => $traceability,
                ];

                $supply = $flowService->recordSupply($order, $postedLine);
                $postedLine['inventory_supply_id'] = (int) $supply->id;
                $postedLine['supply_status'] = (string) $supply->status;
                $postedLine['supplied_qty'] = (float) $supply->qty_supply;
                $postedLine['consumed_qty'] = (float) $supply->qty_consumed;
                $postedLine['returned_qty'] = (float) $supply->qty_returned;

                $postedIssueLines[] = $postedLine;
            }

            $order->update([
                'material_issue_lines' => $postedIssueLines,
                'material_issued_at' => now(),
                'material_issued_by' => auth()->id() ?? 1,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'data' => array_merge(
                $this->buildSupplyStatus($order->fresh()),
                ['inventory_flow' => $this->inventoryFlowService()->summarizeOrderFlow($order->fresh())]
            ),
        ]);
    }

    public function inventoryFlow(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->inventoryFlowService()->summarizeOrderFlow($order),
        ]);
    }

    public function returnSupply(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);
        $validated = $request->validate([
            'tag_no' => 'required|string',
            'qty_return' => 'nullable|numeric|min:0.0001',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $return = $this->inventoryFlowService()->returnSupply(
                $order,
                (string) $validated['tag_no'],
                array_key_exists('qty_return', $validated) ? (float) $validated['qty_return'] : null,
                ['notes' => (string) ($validated['notes'] ?? '')]
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        $updatedIssueLines = collect($order->fresh()->material_issue_lines ?? [])
            ->map(function (array $line) use ($return) {
                if (strtoupper(trim((string) ($line['tag_number'] ?? ''))) !== strtoupper(trim((string) $return->tag_number))) {
                    return $line;
                }

                $alreadyReturned = (float) ($line['returned_qty'] ?? 0);
                $newReturned = round($alreadyReturned + (float) $return->qty_return, 4);
                $issuedQty = (float) ($line['qty'] ?? 0);
                $backflushedQty = (float) ($line['backflushed_qty'] ?? 0);
                $remaining = max(0, round($issuedQty - $backflushedQty - $newReturned, 4));

                $line['returned_qty'] = $newReturned;
                $line['returned_at'] = optional($return->returned_at)->toDateTimeString();
                $line['remaining_after_return'] = $remaining;
                $line['supply_status'] = $remaining <= 0 ? 'closed' : 'partial';

                return $line;
            })
            ->values()
            ->all();

        $order->update(['material_issue_lines' => $updatedIssueLines]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'returned' => [
                    'id' => (int) $return->id,
                    'tag_number' => (string) $return->tag_number,
                    'qty_return' => (float) $return->qty_return,
                    'from_location_code' => (string) ($return->from_location_code ?? ''),
                    'to_location_code' => (string) ($return->to_location_code ?? ''),
                    'returned_at' => optional($return->returned_at)->toDateTimeString(),
                ],
                'inventory_flow' => $this->inventoryFlowService()->summarizeOrderFlow($order->fresh()),
            ],
        ]);
    }
}
