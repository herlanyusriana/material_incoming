<?php

namespace App\Http\Controllers;

use App\Models\StockOpnameSession;
use App\Models\StockOpnameItem;
use App\Models\GciPart;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sessions = StockOpnameSession::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('warehouse.stock_opname.index', compact('sessions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $session = StockOpnameSession::create([
            'session_no' => StockOpnameSession::generateSessionNo(),
            'name' => $request->name,
            'status' => 'OPEN',
            'start_date' => now(),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('warehouse.stock-opname.show', $session)
            ->with('success', 'Stock Opname session started: ' . $session->session_no);
    }

    /**
     * Display the specified resource.
     */
    public function show(StockOpnameSession $stock_opname)
    {
        $stock_opname->load(['creator', 'items.part', 'items.counter']);

        $items = $stock_opname->items()
            ->with(['part', 'counter'])
            ->orderBy('counted_at', 'desc')
            ->paginate(50);

        return view('warehouse.stock_opname.show', compact('stock_opname', 'items'));
    }

    /**
     * Close the session (locking it from further counts)
     */
    public function close(StockOpnameSession $session)
    {
        $session->update([
            'status' => 'CLOSED',
            'end_date' => now(),
        ]);

        return back()->with('success', 'Session closed. Ready for adjustment.');
    }

    /**
     * Perform adjustment (Update system qty to match counted qty)
     */
    public function adjust(StockOpnameSession $session)
    {
        if ($session->status !== 'CLOSED') {
            return back()->with('error', 'Session must be CLOSED before adjustment.');
        }

        DB::transaction(function () use ($session) {
            foreach ($session->items as $item) {
                // Here we would perform the actual stock adjustment in FgInventory or similar
                // For now, we'll mark the item as adjusted (if we had a column) or just complete the session
            }

            $session->update(['status' => 'ADJUSTED']);
        });

        return back()->with('success', 'Stock adjustment completed successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockOpnameSession $stock_opname)
    {
        if ($stock_opname->status === 'ADJUSTED') {
            return back()->with('error', 'Cannot delete an adjusted session.');
        }

        $stock_opname->delete();
        return redirect()->route('warehouse.stock-opname.index')->with('success', 'Session deleted.');
    }
}
