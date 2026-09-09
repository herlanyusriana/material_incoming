<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WarehouseQcController;
use App\Models\NewSchema\Incoming\IncomingArrival as Arrival;
use App\Models\NewSchema\Incoming\IncomingArrivalItem as ArrivalItem;
use App\Models\NewSchema\Incoming\IncomingReceive as Receive;
use App\Services\ReceiveMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * JSON API untuk Flutter "Material Tracker" — incoming receiving.
 * Slim single-tag receive; full control (locations, bundles) tetap via web.
 */
class IncomingApiController extends Controller
{
    public function __construct(private ReceiveMaterialService $receiveService)
    {
    }

    public function departures(): JsonResponse
    {
        $arrivals = Arrival::query()
            ->with(['vendor:id,vendor_name,vendor_type', 'items.receives'])
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $arrivals->map(function (Arrival $arrival) {
                return [
                    'id' => $arrival->id,
                    'invoice_no' => $arrival->invoice_no,
                    'arrival_no' => $arrival->arrival_no,
                    'vendor_name' => $arrival->vendor?->vendor_name,
                    'items' => $arrival->items->map(function (ArrivalItem $item) {
                        $received = (float) $item->receives()->sum('qty');

                        return [
                            'id' => $item->id,
                            'part_no' => $item->gciPart?->part_no,
                            'qty_goods' => (float) $item->qty_goods,
                            'unit_goods' => $item->unit_goods,
                            'qty_received' => $received,
                            'qty_remaining' => (float) $item->qty_goods - $received,
                        ];
                    })->values(),
                ];
            }),
        ]);
    }

    public function receive(Request $request, ArrivalItem $arrivalItem): JsonResponse
    {
        $validated = $request->validate([
            'receive_date' => ['required', 'date'],
            'tag' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0.0001'],
            'qc_status' => ['required', 'in:pass,reject'],
        ]);

        $arrivalItem->loadMissing(['arrival.vendor']);
        $goodsUnit = strtoupper($arrivalItem->unit_goods ?? 'KGM');

        $tagData = [[
            'tag' => trim($validated['tag']),
            'qty' => $validated['qty'],
            'qc_status' => $validated['qc_status'],
        ]];

        try {
            $this->receiveService->ensureTagsUniqueForArrivalItem($arrivalItem, $tagData, 'tags');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['ok' => false, 'message' => 'Tag sudah pernah dipakai pada item ini.'], 422);
        }

        $totalRequested = (float) $validated['qty'];
        $totalReceived = (float) $arrivalItem->receives()->sum('qty');
        $remainingQty = (float) $arrivalItem->qty_goods - $totalReceived;
        if ($totalRequested > $remainingQty + 1e-9) {
            return response()->json([
                'ok' => false,
                'message' => "Qty melebihi sisa ({$remainingQty} {$goodsUnit}).",
            ], 422);
        }

        $receiveAt = Carbon::parse($validated['receive_date'])->setTimeFromTimeString(now()->format('H:i:s'));
        $partId = $this->receiveService->resolveVendorPartId($arrivalItem);
        $gciPartId = $this->receiveService->resolveGciPartId($arrivalItem);

        $receive = DB::transaction(function () use ($validated, $arrivalItem, $goodsUnit, $partId, $gciPartId, $receiveAt) {
            $tag = $this->receiveService->normalizeTag($validated['tag']);

            $receive = $arrivalItem->receives()->create([
                'tag' => $tag,
                'qty' => $validated['qty'],
                'bundle_unit' => null,
                'bundle_qty' => 0,
                'weight' => $goodsUnit === 'KGM' ? $validated['qty'] : null,
                'net_weight' => $goodsUnit === 'KGM' ? $validated['qty'] : null,
                'gross_weight' => null,
                'qty_unit' => $goodsUnit,
                'ata_date' => $receiveAt,
                'qc_status' => $validated['qc_status'],
                'jo_po_number' => null,
                'truck_no' => null,
                'location_code' => null,
            ]);

            $resolvedTag = $this->receiveService->resolveReceiveTag($validated['tag'], (int) $receive->id, $receiveAt);
            if ($resolvedTag !== null && $receive->tag !== $resolvedTag) {
                $receive->update(['tag' => $resolvedTag]);
                $receive->tag = $resolvedTag;
            }

            if ($validated['qc_status'] === 'pass') {
                WarehouseQcController::postReceivingStock($receive);
            }

            return $receive;
        });

        $arrival = $arrivalItem->arrival()->with('items.receives')->first();
        if ($arrival && !$this->receiveService->hasPendingReceives($arrival) && empty($arrival->transaction_no)) {
            $arrival->transaction_no = Arrival::generateTransactionNo($receiveAt->toDateString());
            $arrival->save();
        }

        return response()->json([
            'ok' => true,
            'message' => "Tag {$receive->tag} diterima ({$validated['qty']} {$goodsUnit}).",
            'data' => ['receive_id' => $receive->id],
        ]);
    }
}
