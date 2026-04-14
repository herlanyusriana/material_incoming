<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\Bom;
use App\Models\GciInventory;
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

        // Cek fisik material di GciInventory
        $inventory = GciInventory::where('batch_no', $tagNo)->with('part')->first();
        if (!$inventory) {
            return response()->json(['status' => 'error', 'message' => "Label tidak dikenali (Bukan Label Internal/RM GCI)."], 422);
        }

        // Cek apakah material ini dibutuhkan di BOM mesin
        $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
        if (!$bom) {
            return response()->json(['status' => 'error', 'message' => "Mesin ini tidak memiliki BOM aktif!"], 422);
        }

        $reqs = $bom->getTotalMaterialRequirements($order->qty_planned);
        $materialSesuaiBom = false;

        foreach ($reqs as $req) {
            if (($req['make_or_buy'] ?? '') === 'BUY' && $req['part']?->id === $inventory->gci_part_id) {
                $materialSesuaiBom = true;
                break;
            }
        }

        if (!$materialSesuaiBom) {
            return response()->json(['status' => 'error', 'message' => "ERROR: Material {$inventory->part?->part_no} TIDAK ADA dalam resep BOM mesin ini!"], 422);
        }

        // Jika lolos validasi, save ke lines
        $tags[] = [
            'tag_number' => $tagNo,
            'part_no' => $inventory->part?->part_no,
            'qty' => (float) $inventory->on_hand,
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
