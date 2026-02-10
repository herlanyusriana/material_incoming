<?php

namespace App\Http\Controllers;

use App\Models\OutgoingJigSetting;
use App\Models\OutgoingJigPlan;
use App\Models\CustomerPart;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OutgoingJigController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from')
            ? Carbon::parse($request->input('date_from'))
            : now()->startOfDay();

        $dateTo = $request->input('date_to')
            ? Carbon::parse($request->input('date_to'))
            : now()->addDays(5)->startOfDay();

        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // Generate date range
        $days = [];
        $cursor = $dateFrom->copy();
        while ($cursor->lte($dateTo)) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        // Fetch settings with plans in range
        $settings = OutgoingJigSetting::query()
            ->with([
                'customerPart',
                'plans' => function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('plan_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);
                }
            ])
            ->orderBy('line')
            ->get()
            ->sortBy(fn($s) => $s->line . $s->customerPart?->customer_part_name);

        $customerParts = CustomerPart::query()
            ->select('id', 'customer_part_no', 'customer_part_name', 'case_name')
            ->orderBy('customer_part_name')
            ->orderBy('customer_part_no')
            ->get();

        return view('outgoing.input_jig', compact('settings', 'days', 'dateFrom', 'dateTo', 'customerParts'));
    }

    public function storeRow(Request $request)
    {
        $request->validate([
            'line' => 'required|string',
            'customer_part_id' => 'required|exists:customer_parts,id',
        ]);

        $line = strtoupper(trim($request->line));

        // Auto-fill UPH from existing settings on the same line
        $defaultUph = OutgoingJigSetting::where('line', $line)
            ->where('uph', '>', 0)
            ->value('uph') ?? 0;

        OutgoingJigSetting::firstOrCreate(
            [
                'line' => $line,
                'customer_part_id' => $request->customer_part_id,
            ],
            [
                'uph' => $defaultUph,
            ]
        );

        return back()->with('success', 'Row added successfully');
    }

    public function updateUph(Request $request, OutgoingJigSetting $setting)
    {
        $request->validate(['uph' => 'required|integer|min:0']);

        // Update all items in the same line (UPH is per-line)
        OutgoingJigSetting::where('line', $setting->line)
            ->update(['uph' => $request->uph]);

        return response()->json(['success' => true]);
    }

    public function updatePlan(Request $request, OutgoingJigSetting $setting)
    {
        $request->validate([
            'date' => 'required|date',
            'qty' => 'required|integer|min:0',
        ]);

        OutgoingJigPlan::updateOrCreate(
            [
                'jig_setting_id' => $setting->id,
                'plan_date' => $request->date,
            ],
            [
                'jig_qty' => $request->qty
            ]
        );

        return response()->json(['success' => true]);
    }

    public function deleteRow(OutgoingJigSetting $setting)
    {
        $setting->delete();
        return back()->with('success', 'Row deleted.');
    }
}
