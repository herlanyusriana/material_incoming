<?php

namespace App\Http\Controllers;

use App\Models\SubcountBatch;

class SubcountController extends Controller
{
    public function index()
    {
        $subcounts = SubcountBatch::query()
            ->with('subconOrder.vendor')
            ->withCount('records')
            ->latest('received_at')
            ->latest()
            ->paginate(20);

        return view('subcounts.index', compact('subcounts'));
    }

    public function show(SubcountBatch $subcount)
    {
        $subcount->load(['records', 'subconOrder.vendor', 'subconOrder.rmPart', 'subconOrder.gciPart']);

        return view('subcounts.show', compact('subcount'));
    }
}
