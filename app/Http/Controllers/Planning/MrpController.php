<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\Inventory;
use App\Models\MrpProductionPlan;
use App\Models\MrpPurchasePlan;
use App\Models\MrpRun;
use App\Models\Mps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MrpController extends Controller
{
    private function validateMinggu(string $field = 'minggu'): array
    {
        return [$field => ['required', 'string', 'regex:/^\d{4}-W(0[1-9]|[1-4][0-9]|5[0-3])$/']];
    }

    public function index(Request $request)
    {
        $minggu = $request->query('minggu') ?: now()->format('o-\\WW');

        $run = MrpRun::query()
            ->with(['purchasePlans.part', 'productionPlans.part'])
            ->where('minggu', $minggu)
            ->latest('id')
            ->first();

        return view('planning.mrp.index', compact('minggu', 'run'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate($this->validateMinggu());
        $minggu = $validated['minggu'];

        $approvedMps = Mps::query()
            ->where('minggu', $minggu)
            ->where('status', 'approved')
            ->with('part')
            ->get();

        if ($approvedMps->isEmpty()) {
            return back()->with('error', 'MRP requires approved MPS.');
        }

        DB::transaction(function () use ($minggu, $approvedMps, $request) {
            $run = MrpRun::create([
                'minggu' => $minggu,
                'status' => 'completed',
                'run_by' => $request->user()?->id,
                'run_at' => now(),
            ]);

            foreach ($approvedMps as $row) {
                MrpProductionPlan::create([
                    'mrp_run_id' => $run->id,
                    'part_id' => $row->part_id,
                    'planned_qty' => $row->planned_qty,
                ]);
            }

            $requirements = [];
            foreach ($approvedMps as $row) {
                $bom = Bom::query()->with('items')->where('part_id', $row->part_id)->first();
                if (!$bom) {
                    continue;
                }

                foreach ($bom->items as $item) {
                    $componentId = (int) $item->component_part_id;
                    $requirements[$componentId] = ($requirements[$componentId] ?? 0)
                        + ((float) $row->planned_qty * (float) $item->usage_qty);
                }
            }

            foreach ($requirements as $partId => $requiredQty) {
                $inventory = Inventory::query()->where('part_id', $partId)->first();
                $onHand = (float) ($inventory->on_hand ?? 0);
                $onOrder = (float) ($inventory->on_order ?? 0);
                $netRequired = max(0, $requiredQty - $onHand - $onOrder);

                MrpPurchasePlan::create([
                    'mrp_run_id' => $run->id,
                    'part_id' => $partId,
                    'required_qty' => $requiredQty,
                    'on_hand' => $onHand,
                    'on_order' => $onOrder,
                    'net_required' => $netRequired,
                ]);
            }
        });

        return redirect()->route('planning.mrp.index', ['minggu' => $minggu])
            ->with('success', 'MRP generated.');
    }
}
