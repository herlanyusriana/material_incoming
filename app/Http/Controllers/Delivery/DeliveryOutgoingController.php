<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\SalesOrder;
use App\Models\Customer;
use App\Models\Trucking;
use App\Services\DeliveryOutgoingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryOutgoingController extends Controller
{
    protected DeliveryOutgoingService $deliveryService;

    public function __construct(DeliveryOutgoingService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * Display a listing of the delivery notes
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $customerId = $request->query('customer_id');

        $query = DeliveryNote::with(['customer', 'truck', 'items']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->where('delivery_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('delivery_date', '<=', $dateTo);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $deliveryNotes = $query->orderBy('delivery_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $customers = Customer::orderBy('name')->get();
        $trucks = Trucking::orderBy('name')->get();

        return view('delivery.outgoing.index', compact('deliveryNotes', 'customers', 'trucks'));
    }

    /**
     * Show form to create a new delivery note
     */
    public function create(Request $request)
    {
        $customerId = $request->query('customer_id');
        $salesOrders = $this->deliveryService->getReadyForDeliveryOrders($customerId);

        $customers = Customer::orderBy('name')->get();
        $trucks = Trucking::orderBy('name')->get();

        return view('delivery.outgoing.create', compact('salesOrders', 'customers', 'trucks', 'customerId'));
    }

    /**
     * Store a newly created delivery note
     */
    public function store(Request $request)
    {
        $request->validate([
            'sales_order_ids' => 'required|array|min:1',
            'sales_order_ids.*' => 'integer|exists:sales_orders,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'truck_id' => 'nullable|integer|exists:trucking_companies,id',
            'driver_id' => 'nullable|integer|exists:users,id', // Assuming drivers are stored as users
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $deliveryNote = $this->deliveryService->createDeliveryNote(
                $request->sales_order_ids,
                $request->customer_id,
                $request->truck_id,
                $request->driver_id,
                [
                    'delivery_date' => $request->delivery_date,
                    'notes' => $request->notes,
                    'created_by' => auth()->id(),
                ]
            );

            return redirect()->route('delivery.outgoing.show', $deliveryNote->id)
                ->with('success', 'Delivery note created successfully: ' . $deliveryNote->delivery_no);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified delivery note
     */
    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'truck', 'items.salesOrder', 'items.part', 'creator']);

        return view('delivery.outgoing.show', compact('deliveryNote'));
    }

    /**
     * Show form to edit delivery note
     */
    public function edit(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'truck', 'items']);
        
        $customers = Customer::orderBy('name')->get();
        $trucks = Trucking::orderBy('name')->get();

        return view('delivery.outgoing.edit', compact('deliveryNote', 'customers', 'trucks'));
    }

    /**
     * Update the specified delivery note
     */
    public function update(Request $request, DeliveryNote $deliveryNote)
    {
        $request->validate([
            'truck_id' => 'nullable|integer|exists:trucking_companies,id',
            'driver_id' => 'nullable|integer|exists:users,id',
            'status' => 'required|string|in:prepared,assigned,in_transit,delivered,cancelled',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $deliveryNote->update([
                'truck_id' => $request->truck_id,
                'driver_id' => $request->driver_id,
                'status' => $request->status,
                'delivery_date' => $request->delivery_date,
                'notes' => $request->notes,
            ]);

            return redirect()->route('delivery.outgoing.show', $deliveryNote->id)
                ->with('success', 'Delivery note updated successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Assign delivery note to a truck
     */
    public function assignTruck(Request $request, DeliveryNote $deliveryNote)
    {
        $request->validate([
            'truck_id' => 'required|integer|exists:trucking_companies,id',
        ]);

        try {
            $this->deliveryService->assignToTruck($deliveryNote->id, $request->truck_id);

            return redirect()->back()->with('success', 'Delivery note assigned to truck successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Assign delivery note to a driver
     */
    public function assignDriver(Request $request, DeliveryNote $deliveryNote)
    {
        $request->validate([
            'driver_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $this->deliveryService->assignToDriver($deliveryNote->id, $request->driver_id);

            return redirect()->back()->with('success', 'Delivery note assigned to driver successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update delivery status
     */
    public function updateStatus(Request $request, DeliveryNote $deliveryNote)
    {
        $request->validate([
            'status' => 'required|string|in:prepared,assigned,in_transit,delivered,cancelled',
        ]);

        try {
            $this->deliveryService->updateDeliveryStatus($deliveryNote->id, $request->status);

            return redirect()->back()->with('success', 'Delivery status updated successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get sales orders ready for delivery via AJAX
     */
    public function getReadyOrders(Request $request)
    {
        $customerId = $request->query('customer_id');
        $salesOrders = $this->deliveryService->getReadyForDeliveryOrders($customerId);

        return response()->json([
            'success' => true,
            'data' => $salesOrders
        ]);
    }
}