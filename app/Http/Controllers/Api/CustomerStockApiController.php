<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\GciPart;
use App\Models\StockAtCustomer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CustomerStockApiController extends Controller
{
    protected function applyLgCustomerScope($query)
    {
        return $query->where(function ($builder) {
            $builder->where('code', 'like', '%LG%')
                ->orWhere('name', 'like', '%LG%');
        });
    }

    protected function resolveLgCustomerOrFail(int $customerId): Customer
    {
        return $this->applyLgCustomerScope(Customer::query())
            ->findOrFail($customerId);
    }

    public function customers(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $customers = $this->applyLgCustomerScope(Customer::query())
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($builder) use ($q) {
                    $builder->where('name', 'like', '%' . $q . '%')
                        ->orWhere('code', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status']);

        return response()->json([
            'data' => $customers->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'status' => $customer->status,
            ])->values(),
        ]);
    }

    public function parts(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $customer = $this->resolveLgCustomerOrFail((int) $validated['customer_id']);

        $parts = $customer->gciParts()
            ->where('classification', 'FG')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($builder) use ($q) {
                    $builder->where('part_no', 'like', '%' . $q . '%')
                        ->orWhere('part_name', 'like', '%' . $q . '%')
                        ->orWhere('model', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('part_no')
            ->limit(50)
            ->get(['gci_parts.id', 'part_no', 'part_name', 'model', 'classification', 'status']);

        if ($parts->isEmpty()) {
            $parts = GciPart::query()
                ->where('customer_id', $customer->id)
                ->where('classification', 'FG')
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($builder) use ($q) {
                        $builder->where('part_no', 'like', '%' . $q . '%')
                            ->orWhere('part_name', 'like', '%' . $q . '%')
                            ->orWhere('model', 'like', '%' . $q . '%');
                    });
                })
                ->orderBy('part_no')
                ->limit(50)
                ->get(['id', 'part_no', 'part_name', 'model', 'classification', 'status']);
        }

        return response()->json([
            'data' => $parts->map(fn (GciPart $part) => [
                'id' => $part->id,
                'part_no' => $part->part_no,
                'part_name' => $part->part_name,
                'model' => $part->model,
                'classification' => $part->classification,
                'status' => $part->status,
            ])->values(),
        ]);
    }

    public function entries(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'stock_date' => ['required', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $this->resolveLgCustomerOrFail((int) $validated['customer_id']);

        $entries = StockAtCustomer::query()
            ->with(['customer:id,code,name', 'part:id,part_no,part_name,model'])
            ->where('customer_id', (int) $validated['customer_id'])
            ->whereDate('stock_date', $validated['stock_date'])
            ->whereHas('part', function ($query) {
                $query->where('classification', 'FG');
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($builder) use ($q) {
                    $builder->where('part_no', 'like', '%' . $q . '%')
                        ->orWhere('part_name', 'like', '%' . $q . '%')
                        ->orWhere('model', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('part_no')
            ->get();

        return response()->json([
            'data' => $entries->map(fn (StockAtCustomer $entry) => [
                'id' => $entry->id,
                'stock_date' => optional($entry->stock_date)->format('Y-m-d'),
                'customer_id' => $entry->customer_id,
                'customer_name' => $entry->customer?->name,
                'gci_part_id' => $entry->gci_part_id,
                'part_no' => $entry->part_no,
                'part_name' => $entry->part_name,
                'model' => $entry->model,
                'status' => $entry->status,
                'qty' => (float) $entry->qty,
            ])->values(),
        ]);
    }

    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'stock_date' => ['required', 'date'],
            'gci_part_id' => ['nullable', 'integer', 'exists:gci_parts,id'],
            'part_no' => ['required_without:gci_part_id', 'nullable', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0'],
        ]);

        $customerId = (int) $validated['customer_id'];
        $this->resolveLgCustomerOrFail($customerId);
        $stockDate = Carbon::parse($validated['stock_date'])->format('Y-m-d');
        $qty = round((float) $validated['qty'], 3);
        $gciPartId = !empty($validated['gci_part_id']) ? (int) $validated['gci_part_id'] : null;
        $partNo = strtoupper(trim((string) ($validated['part_no'] ?? '')));

        $part = null;
        if ($gciPartId) {
            $part = GciPart::query()->find($gciPartId);
        } elseif ($partNo !== '') {
            $part = GciPart::query()
                ->where('part_no', $partNo)
                ->where(function ($query) use ($customerId) {
                    $query->where('customer_id', $customerId)
                        ->orWhereHas('customers', fn ($sub) => $sub->where('customers.id', $customerId));
                })
                ->first();

            if (!$part) {
                $part = GciPart::query()->where('part_no', $partNo)->first();
            }
        }

        if (!$part || strtoupper((string) $part->classification) !== 'FG') {
            return response()->json([
                'message' => 'Part FG customer LG tidak ditemukan.',
            ], 422);
        }

        $partNo = (string) $part->part_no;

        if ($qty <= 0) {
            StockAtCustomer::query()
                ->where('stock_date', $stockDate)
                ->where('customer_id', $customerId)
                ->where('part_no', $partNo)
                ->delete();

            return response()->json([
                'message' => 'Stock customer dihapus karena qty = 0.',
            ]);
        }

        $entry = StockAtCustomer::query()->updateOrCreate(
            [
                'stock_date' => $stockDate,
                'customer_id' => $customerId,
                'part_no' => $partNo,
            ],
            [
                'gci_part_id' => $part->id,
                'part_name' => $part->part_name,
                'model' => $part->model,
                'status' => $part->status,
                'qty' => $qty,
            ]
        );

        return response()->json([
            'message' => 'Stock customer berhasil disimpan.',
            'data' => [
                'id' => $entry->id,
                'stock_date' => optional($entry->stock_date)->format('Y-m-d'),
                'customer_id' => $entry->customer_id,
                'gci_part_id' => $entry->gci_part_id,
                'part_no' => $entry->part_no,
                'part_name' => $entry->part_name,
                'model' => $entry->model,
                'qty' => (float) $entry->qty,
            ],
        ]);
    }
}
