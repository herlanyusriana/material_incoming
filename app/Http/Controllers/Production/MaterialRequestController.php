<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\ProductionMaterialRequest;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', ''));
        $month = trim((string) $request->query('month', ''));
        $q = trim((string) $request->query('q', ''));

        $query = ProductionMaterialRequest::query()
            ->with([
                'productionOrder.part:id,part_no,part_name',
                'requester:id,name',
            ])
            ->withCount('items')
            ->latest('request_date')
            ->latest('id');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($month !== '') {
            $query->whereYear('request_date', substr($month, 0, 4))
                ->whereMonth('request_date', substr($month, 5, 2));
        }

        if ($q !== '') {
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('request_no', 'like', '%' . $q . '%')
                    ->orWhere('reason', 'like', '%' . $q . '%')
                    ->orWhereHas('productionOrder', function ($orderQuery) use ($q) {
                        $orderQuery->where('production_order_number', 'like', '%' . $q . '%')
                            ->orWhere('transaction_no', 'like', '%' . $q . '%')
                            ->orWhereHas('part', function ($partQuery) use ($q) {
                                $partQuery->where('part_no', 'like', '%' . $q . '%')
                                    ->orWhere('part_name', 'like', '%' . $q . '%');
                            });
                    });
            });
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('production.material-request.index', compact('requests', 'status', 'month', 'q'));
    }

    public function create(Request $request)
    {
        $selectedOrderId = $request->integer('production_order_id') ?: null;

        $orders = ProductionOrder::query()
            ->with('part:id,part_no,part_name')
            ->whereNull('deleted_at')
            ->whereIn('status', ['kanban_released', 'material_hold', 'resource_hold', 'released', 'in_production'])
            ->orderByDesc('plan_date')
            ->orderByDesc('id')
            ->get();

        $parts = Part::query()
            ->leftJoin('inventories', 'inventories.part_id', '=', 'parts.id')
            ->select(
                'parts.*',
                DB::raw('COALESCE(inventories.on_hand, 0) as stock_on_hand'),
                DB::raw('COALESCE(inventories.on_order, 0) as stock_on_order')
            )
            ->orderBy('parts.part_no')
            ->get();

        return view('production.material-request.create', compact('orders', 'parts', 'selectedOrderId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'production_order_id' => ['nullable', 'exists:production_orders,id'],
            'request_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.part_id' => ['required', 'integer'],
            'items.*.qty_requested' => ['required', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $partIds = collect($validated['items'])
            ->pluck('part_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $parts = Part::query()
            ->leftJoin('inventories', 'inventories.part_id', '=', 'parts.id')
            ->select(
                'parts.*',
                DB::raw('COALESCE(inventories.on_hand, 0) as stock_on_hand'),
                DB::raw('COALESCE(inventories.on_order, 0) as stock_on_order')
            )
            ->whereIn('parts.id', $partIds)
            ->get()
            ->keyBy('id');

        if ($parts->count() !== $partIds->count()) {
            return back()
                ->withErrors(['items' => 'Ada item material yang tidak valid.'])
                ->withInput();
        }

        $requestModel = DB::transaction(function () use ($validated, $parts, $request) {
            $materialRequest = ProductionMaterialRequest::create([
                'request_no' => ProductionMaterialRequest::generateRequestNo($validated['request_date']),
                'production_order_id' => $validated['production_order_id'] ?? null,
                'request_date' => $validated['request_date'],
                'status' => 'requested',
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'requested_by' => $request->user()?->id,
            ]);

            foreach ($validated['items'] as $item) {
                $part = $parts[(int) $item['part_id']];

                $materialRequest->items()->create([
                    'part_id' => $part->id,
                    'part_no' => $part->part_no,
                    'part_name' => $part->part_name,
                    'uom' => $part->uom ?? 'PCS',
                    'qty_requested' => (float) $item['qty_requested'],
                    'qty_issued' => 0,
                    'stock_on_hand' => (float) ($part->stock_on_hand ?? 0),
                    'stock_on_order' => (float) ($part->stock_on_order ?? 0),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $materialRequest;
        });

        return redirect()
            ->route('production.material-request.show', $requestModel)
            ->with('success', 'Material request tambahan berhasil dibuat.');
    }

    public function show(ProductionMaterialRequest $materialRequest)
    {
        $materialRequest->load([
            'productionOrder.part:id,part_no,part_name',
            'requester:id,name',
            'items.part:id,part_no,part_name',
        ]);

        return view('production.material-request.show', compact('materialRequest'));
    }
}
