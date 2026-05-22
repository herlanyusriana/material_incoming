<?php

namespace App\Http\Controllers;

use App\Models\SubcountBatch;

class SubcountController extends Controller
{
    public function index()
    {
        $subcounts = SubcountBatch::query()
            ->withCount('records')
            ->latest('received_at')
            ->latest()
            ->paginate(20);

        return view('subcounts.index', compact('subcounts'));
    }

    public function show(SubcountBatch $subcount)
    {
        $subcount->load('records');

        return view('subcounts.show', compact('subcount'));
    }
}
