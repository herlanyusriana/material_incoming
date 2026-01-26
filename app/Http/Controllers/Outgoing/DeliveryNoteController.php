<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPo;
use App\Models\DeliveryNote;
use App\Models\DnItem;
use App\Models\FgInventory;
use App\Models\GciPart;
use App\Models\LocationInventory;
use App\Models\Part;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeliveryNoteController extends Controller
{
    private const STATUS_DRAFT = 'draft';
    private const STATUS_KITTING = 'kitting';
    private const STATUS_READY_TO_PICK = 'ready_to_pick';
    private const STATUS_PICKING = 'picking';
    private const STATUS_READY_TO_SHIP = 'ready_to_ship';
    private const STATUS_SHIPPED = 'shipped';

    public function index()
    {
        $deliveryNotes = DeliveryNote::with(['customer', 'items.part'])
            ->latest()
            ->paginate(25);

        return view('outgoing.delivery_notes.index', compact('deliveryNotes'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $gciParts = GciPart::where('classification', 'FG')->orderBy('part_no')->get();
        
        return view('outgoing.delivery_notes.create', compact('customers', 'gciParts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dn_no' => ['required', 'string', 'unique:delivery_notes,dn_no'],
            'customer_id' => ['required', 'exists:customers,id'],
            'delivery_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.gci_part_id' => ['required', 'exists:gci_parts,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'items.*.customer_po_id' => ['nullable', 'exists:customer_pos,id'],
        ]);

        DB::transaction(function () use ($validated) {
            $dn = DeliveryNote::create([
                'dn_no' => $validated['dn_no'],
                'customer_id' => $validated['customer_id'],
                'delivery_date' => $validated['delivery_date'],
                'notes' => $validated['notes'],
                'status' => 'draft',
            ]);

            foreach ($validated['items'] as $item) {
                DnItem::create([
                    'dn_id' => $dn->id,
                    'gci_part_id' => $item['gci_part_id'],
                    'qty' => $item['qty'],
                    'customer_po_id' => $item['customer_po_id'] ?? null,
                ]);
            }
        });

        return redirect()->route('outgoing.delivery-notes.index')->with('success', 'Delivery Note created.');
    }

    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'items.part', 'items.customerPo', 'items.picker']);

        $kittingLocationsByItem = [];
        if (Schema::hasTable('warehouse_locations') && Schema::hasTable('location_inventory')) {
            $locationCodes = WarehouseLocation::query()
                ->where('status', 'ACTIVE')
                ->orderBy('location_code')
                ->pluck('location_code')
                ->all();

            $partsByNo = Part::query()
                ->whereIn('part_no', $deliveryNote->items->map(fn ($i) => $i->part?->part_no)->filter()->unique()->values())
                ->get()
                ->keyBy('part_no');

            $partIds = $partsByNo->pluck('id')->values();

            $stocks = LocationInventory::query()
                ->whereIn('part_id', $partIds)
                ->whereIn('location_code', $locationCodes)
                ->where('qty_on_hand', '>', 0)
                ->get()
                ->groupBy('part_id');

            foreach ($deliveryNote->items as $item) {
                $gciPartNo = (string) ($item->part?->part_no ?? '');
                $part = $partsByNo[$gciPartNo] ?? null;
                if (!$part) {
                    $kittingLocationsByItem[$item->id] = [];
                    continue;
                }

                $kittingLocationsByItem[$item->id] = ($stocks[$part->id] ?? collect())
                    ->sortBy('location_code')
                    ->map(fn ($s) => ['code' => $s->location_code, 'qty' => (float) $s->qty_on_hand])
                    ->values()
                    ->all();
            }
        }

        return view('outgoing.delivery_notes.show', compact('deliveryNote', 'kittingLocationsByItem'));
    }

    public function pickingScan(DeliveryNote $deliveryNote)
    {
        if (!in_array($deliveryNote->status, [self::STATUS_PICKING, self::STATUS_READY_TO_PICK], true)) {
            return back()->with('error', 'Delivery Note must be ready to pick / picking.');
        }

        $deliveryNote->load(['customer', 'items.part']);

        return view('outgoing.delivery_notes.picking_scan', compact('deliveryNote'));
    }

    public function pickingScanStore(Request $request, DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->status !== self::STATUS_PICKING) {
            return back()->with('error', 'Picking scan only available when status is PICKING. Click START PICKING first.');
        }

        $validated = $request->validate([
            'location_code' => ['required', 'string', 'max:50'],
            'part_no' => ['required', 'string', 'max:100'],
            'qty' => ['nullable', 'numeric', 'min:0.0001'],
        ]);

        $locationCode = strtoupper(trim((string) $validated['location_code']));
        $partNo = strtoupper(trim((string) $validated['part_no']));
        $qty = (float) ($validated['qty'] ?? 1);

        $deliveryNote->loadMissing(['items.part']);

        $item = $deliveryNote->items
            ->first(function ($i) use ($partNo, $locationCode) {
                $pno = strtoupper(trim((string) ($i->part?->part_no ?? '')));
                $loc = strtoupper(trim((string) ($i->kitting_location_code ?? '')));

                $remaining = (float) $i->qty - (float) ($i->picked_qty ?? 0);
                return $pno === $partNo && $loc === $locationCode && $remaining > 0;
            });

        if (!$item) {
            $msg = "No matching DN item remaining for part {$partNo} at location {$locationCode}.";
            return back()->with('error', $msg);
        }

        $remaining = (float) $item->qty - (float) ($item->picked_qty ?? 0);
        if ($qty > $remaining) {
            return back()->with('error', "Pick qty too large. Remaining {$remaining} for {$partNo}.");
        }

        DB::transaction(function () use ($item, $qty, $request) {
            $locked = DnItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $current = (float) ($locked->picked_qty ?? 0);
            $new = $current + $qty;

            if ($new > (float) $locked->qty) {
                throw new \RuntimeException('Pick qty exceeds required qty.');
            }

            $payload = [
                'picked_qty' => $new,
            ];

            if ($new >= (float) $locked->qty) {
                $payload['picked_at'] = now();
                $payload['picked_by'] = (int) ($request->user()?->id ?? 0) ?: null;
            }

            $locked->update($payload);
        });

        $done = ((float) ($item->picked_qty ?? 0) + $qty) >= (float) $item->qty;
        return back()->with('success', $done ? "Picked complete: {$partNo}" : "Picked: {$partNo} (+{$qty})");
    }

    public function startPicking(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->status !== self::STATUS_READY_TO_PICK) {
            return back()->with('error', 'Delivery Note must finish kitting before picking can start.');
        }

        $deliveryNote->update(['status' => self::STATUS_PICKING]);

        return back()->with('success', 'Picking process started.');
    }

    public function completePicking(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->status !== self::STATUS_PICKING) {
            return back()->with('error', 'Only delivery notes in picking status can be completed.');
        }

        $deliveryNote->loadMissing(['items']);
        $incomplete = $deliveryNote->items->first(fn ($i) => (float) ($i->picked_qty ?? 0) < (float) $i->qty);
        if ($incomplete) {
            return back()->with('error', 'Picking belum lengkap. Pastikan semua item sudah picked qty = required qty.');
        }

        $deliveryNote->update(['status' => self::STATUS_READY_TO_SHIP]);

        return back()->with('success', 'Picking process completed. Ready to ship.');
    }

    public function startKitting(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->status !== self::STATUS_DRAFT) {
            return back()->with('error', 'Only draft delivery notes can start kitting.');
        }

        $deliveryNote->update(['status' => self::STATUS_KITTING]);

        return back()->with('success', 'Kitting process started.');
    }

    public function completeKitting(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->status !== self::STATUS_KITTING) {
            return back()->with('error', 'Only delivery notes in kitting status can be completed.');
        }

        $validated = request()->validate([
            'kitting_locations' => ['required', 'array'],
        ]);

        $deliveryNote->loadMissing(['items.part']);

        if (Schema::hasTable('warehouse_locations') && Schema::hasTable('location_inventory')) {
            $activeLocations = WarehouseLocation::query()
                ->where('status', 'ACTIVE')
                ->pluck('location_code')
                ->flip();

            foreach ($deliveryNote->items as $item) {
                $loc = strtoupper(trim((string) ($validated['kitting_locations'][$item->id] ?? '')));
                if ($loc === '' || !isset($activeLocations[$loc])) {
                    return back()->with('error', "Kitting location wajib diisi & ACTIVE untuk part {$item->part?->part_no}.");
                }

                $gciPartNo = (string) ($item->part?->part_no ?? '');
                $mappedPart = Part::query()->where('part_no', $gciPartNo)->first();
                if (!$mappedPart) {
                    return back()->with('error', "Part master (parts) tidak ditemukan untuk FG {$gciPartNo}. Buat dulu di master Part agar bisa cek stok per lokasi.");
                }

                $available = LocationInventory::getStockByLocation((int) $mappedPart->id, $loc);
                if ($available < (float) $item->qty) {
                    return back()->with('error', "Stok lokasi {$loc} untuk {$gciPartNo} kurang. Available {$available}, need {$item->qty}.");
                }
            }
        } else {
            return back()->with('error', 'Warehouse locations / location inventory not configured. Cannot complete kitting with location validation.');
        }

        DB::transaction(function () use ($deliveryNote, $validated) {
            foreach ($deliveryNote->items as $item) {
                $loc = strtoupper(trim((string) ($validated['kitting_locations'][$item->id] ?? '')));
                $item->update(['kitting_location_code' => $loc !== '' ? $loc : null]);
            }
            $deliveryNote->update(['status' => self::STATUS_READY_TO_PICK]);
        });

        return back()->with('success', 'Kitting process completed. Ready to pick.');
    }

    public function ship(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->status === self::STATUS_SHIPPED) {
            return back()->with('error', 'Delivery Note already shipped.');
        }

        if ($deliveryNote->status !== self::STATUS_READY_TO_SHIP) {
            return back()->with('error', 'Delivery Note must be ready to ship before shipping.');
        }

        $deliveryNote->loadMissing(['items.part']);

        DB::transaction(function () use ($deliveryNote) {
            $deliveryNote->update(['status' => self::STATUS_SHIPPED]);

            foreach ($deliveryNote->items as $item) {
                $loc = strtoupper(trim((string) ($item->kitting_location_code ?? '')));
                if ($loc !== '' && Schema::hasTable('location_inventory') && Schema::hasTable('warehouse_locations')) {
                    $gciPartNo = (string) ($item->part?->part_no ?? '');
                    $mappedPart = Part::query()->where('part_no', $gciPartNo)->first();
                    if (!$mappedPart) {
                        throw new \Exception("Part master (parts) tidak ditemukan untuk FG {$gciPartNo}. Tidak bisa deduct stok per lokasi.");
                    }
                    LocationInventory::consumeStock((int) $mappedPart->id, $loc, (float) $item->qty);
                }

                $inventory = FgInventory::firstOrCreate(
                    ['gci_part_id' => $item->gci_part_id],
                    ['qty_on_hand' => 0]
                );

                $inventory->decrement('qty_on_hand', $item->qty);
            }
        });

        return back()->with('success', 'Delivery Note shipped and inventory deducted.');
    }

    public function destroy(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->status === self::STATUS_SHIPPED) {
            return back()->with('error', 'Cannot delete shipped Delivery Note.');
        }

        $deliveryNote->delete();
        return redirect()->route('outgoing.delivery-notes.index')->with('success', 'Delivery Note deleted.');
    }
}
