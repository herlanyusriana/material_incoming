<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\Bom;
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
        
        // Cukup simpel push raw string atau kita coba tebak qty/part bila punya scanner API canggih
        // Untuk tahap awal: kita record the tag
        $tags[] = [
            'tag_number' => $validated['tag_no'],
            'qty' => 0, // Should be input manually or got from tag API
            'scanned_at' => now()->toDateTimeString()
        ];
        
        $order->update(['material_issue_lines' => $tags]);

        return response()->json(['status' => 'success', 'data' => $tags]);
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
