<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\Bom;
use App\Models\Receive;
use App\Models\LocationInventory;
use Illuminate\Http\Request;

class WarehouseApiController extends Controller
{
    public function pendingWorkOrders()
    {
        $orders = ProductionOrder::with('part')
            ->whereIn('status', ['kanban_released', 'planned']) // depending on when they issue
            ->whereNull('material_handed_over_at')
            ->orderBy('plan_date', 'asc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'wo_number' => $order->production_order_number ?? $order->transaction_no,
                    'part_no' => $order->part?->part_no ?? 'Unknown',
                    'qty_planned' => (int) $order->qty_planned,
                    'status' => $order->status,
                ];
            });

        return response()->json(['status' => 'success', 'data' => $orders]);
    }

    public function getWorkOrder($id)
    {
        $order = ProductionOrder::findOrFail($id);
        $issueLines = $order->material_issue_lines ?? [];
        
        $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
        $requirements = [];
        
        if ($bom) {
            $reqs = $bom->getTotalMaterialRequirements($order->qty_planned);
            foreach ($reqs as $req) {
                 if (in_array($req['make_or_buy'] ?? '', ['BUY', 'B', 'PURCHASE']) &&
                     strtoupper((string) ($req['component_classification'] ?? '')) === 'RM') {
                     $requirements[] = [
                         'gci_part_id' => $req['part']?->id,
                         'part_no' => $req['part']?->part_no ?? 'Unknown',
                         'total_qty' => (float) ($req['total_qty'] ?? 0)
                     ];
                 }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $order->id,
                'requirements' => $requirements,
                'issue_lines' => $issueLines
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
        $qtyAvailable = 0;
        $partNo = 'Unknown';

        // 1. Cek apakah barang sudah masuk rak (Location Inventory)
        $locInv = LocationInventory::where('batch_no', $tagNo)->with('part', 'gciPart')->first();
        if ($locInv) {
            $gciPartId = $locInv->gci_part_id;
            $qtyAvailable = $locInv->qty_on_hand;
            $partNo = $locInv->part?->part_no ?? $locInv->gciPart?->part_no ?? 'Unknown';
        } else {
            // 2. Cek apakah barang masih di area Incoming/Putaway Queue (Receives)
            $receive = Receive::where('tag', $tagNo)->with('arrivalItem.part', 'arrivalItem.gciPartVendor')->first();
            if ($receive) {
                // Determine GCI Part ID from ArrivalItem mappings
                $arrItem = $receive->arrivalItem;
                if ($arrItem) {
                    $gciPartId = $arrItem->gci_part_id 
                        ?? $arrItem->gciPart?->id;
                    
                    $partNo = $arrItem->part?->part_no 
                        ?? $arrItem->gciPartVendor?->vendor_part_no 
                        ?? 'Unknown';
                    
                    // The generic "resolveGciPartId" logic from ReceiveController
                    if (!$gciPartId) {
                        $vendorPartId = (int) ($arrItem->gci_part_vendor_id ?: $arrItem->part_id ?: 0);
                        if ($vendorPartId > 0) {
                            $gciPartId = \App\Models\GciPartVendor::whereKey($vendorPartId)->value('gci_part_id') 
                                ?? \App\Models\Part::whereKey($vendorPartId)->value('gci_part_id');
                        }
                    }
                }
                
                $qtyUnit = strtoupper(trim((string) ($receive->qty_unit ?? '')));
                $qtyAvailable = $qtyUnit === 'COIL' ? (float) ($receive->net_weight ?? 0) : (float) ($receive->qty ?? 0);
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
            if (($req['make_or_buy'] ?? '') === 'BUY' && $req['part']?->id === $gciPartId) {
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
            'scanned_at' => now()->toDateTimeString()
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
        $order->update([
            'material_handed_over_at' => now(),
            'material_handed_over_by' => auth()->id() ?? 1,
            'status' => $order->status === 'planned' ? 'kanban_released' : $order->status
        ]);
        
        return response()->json(['status' => 'success']);
    }
}
