<?php

namespace App\Http\Controllers;

use App\Models\OutgoingJigSetting;
use App\Models\OutgoingJigPlan;
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
                'plans' => function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('plan_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);
                }
            ])
            ->orderBy('line')
            ->orderBy('project_name')
            ->get();

        return view('outgoing.input_jig', compact('settings', 'days', 'dateFrom', 'dateTo'));
    }

    public function storeRow(Request $request)
    {
        $request->validate([
            'line' => 'required|string',
            'project_name' => 'required|string',
        ]);

        OutgoingJigSetting::firstOrCreate([
            'line' => strtoupper(trim($request->line)),
            'project_name' => trim($request->project_name),
        ]);

        return back()->with('success', 'Row added successfully');
    }

    public function updateUph(Request $request, OutgoingJigSetting $setting)
    {
        $request->validate(['uph' => 'required|integer|min:0']);
        $setting->update(['uph' => $request->uph]);
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
