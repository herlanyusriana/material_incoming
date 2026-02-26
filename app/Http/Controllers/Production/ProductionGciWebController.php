<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionGciWorkOrder;

class ProductionGciWebController extends Controller
{
    public function index()
    {
        $workOrders = ProductionGciWorkOrder::with(['hourlyReports', 'downtimes', 'materialLots'])
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('production.gci-dashboard.index', compact('workOrders'));
    }

    public function show($id)
    {
        $workOrder = ProductionGciWorkOrder::with(['hourlyReports', 'downtimes', 'materialLots'])->findOrFail($id);
        return view('production.gci-dashboard.show', compact('workOrder'));
    }
}
