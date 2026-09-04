<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use App\Models\NewSchema\Incoming\IncomingReceive as Receive;
use App\Models\NewSchema\Incoming\IncomingArrivalItem as ArrivalItem;
use App\Models\NewSchema\Incoming\IncomingArrival as Arrival;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\VendorPart;
use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Exports\CompletedInvoiceReceivesExport;
use App\Exports\ImportDocumentRecapExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Support\QrSvg;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Traits\LogsActivity;
use App\Services\ReceiveMaterialService;

class ReceiveController extends Controller
{
    use LogsActivity;

    protected ReceiveMaterialService $receiveService;

    public function __construct(ReceiveMaterialService $receiveService)
    {
        $this->receiveService = $receiveService;
    }

    private function resolveVendorPartId(ArrivalItem $arrivalItem): int
    {
        return $this->receiveService->resolveVendorPartId($arrivalItem);
    }

    private function resolveGciPartId(ArrivalItem $arrivalItem): ?int
    {
        return $this->receiveService->resolveGciPartId($arrivalItem);
    }

    private function ensurePutawayGciPartId(ArrivalItem $arrivalItem, string $errorKey = 'tags'): int
    {
        return $this->receiveService->ensurePutawayGciPartId($arrivalItem, $errorKey);
    }

    private function normalizeTag(?string $tag): ?string
    {
        return $this->receiveService->normalizeTag($tag);
    }

    private function resolveReceiveTag(?string $tag, ?int $receiveId = null, $receivedAt = null): ?string
    {
        return $this->receiveService->resolveReceiveTag($tag, $receiveId, $receivedAt);
    }

    private function hasPendingReceives(Arrival $arrival): bool
    {
        return $this->receiveService->hasPendingReceives($arrival);
    }

    private function ensureTagsUniqueForArrivalItem(ArrivalItem $arrivalItem, array $tags, string $errorKey = 'tags', ?int $ignoreReceiveId = null): void
    {
        $this->receiveService->ensureTagsUniqueForArrivalItem($arrivalItem, $tags, $errorKey, $ignoreReceiveId);
    }

    // Note:
    // TAG fisik boleh sama antar item yang berbeda dalam invoice yang sama.
    // Yang wajib unik hanyalah TAG dalam scope 1 item (arrival_item_id).

    private function ensureCompletedArrivalTransactionNo(Arrival $arrival): void
    {
        $this->receiveService->ensureCompletedArrivalTransactionNo($arrival);
    }

    public function index()
    {
        // Group pending by invoice/departure
        $pendingArrivals = Arrival::with(['vendor', 'items.receives'])
            ->get()
            ->map(function ($arrival) {
                $remaining = $arrival->items->sum(function ($item) {
                    $received = $item->receives->sum('qty');
                    return max(0, $item->qty_goods - $received);
                });
                $arrival->remaining_qty = $remaining;
                $arrival->pending_items_count = $arrival->items->filter(function ($item) {
                    $received = $item->receives->sum('qty');
                    return ($item->qty_goods - $received) > 0;
                })->count();
                return $arrival;
            })
            ->filter(fn($arrival) => $arrival->remaining_qty > 0)
            ->values();

        return view('receives.index', [
            'pendingArrivals' => $pendingArrivals,
        ]);
    }

    public function completed()
    {
        $q = trim((string) request()->query('q', ''));
        $flow = strtolower(trim((string) request()->query('flow', '')));

        // Show completed receives grouped by invoice/departure
        $arrivals = Arrival::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->from('incoming_arrivals as arrivals')
            ->select([
                'arrivals.id',
                'arrivals.arrival_no',
                'arrivals.transaction_no',
                'arrivals.invoice_no',
                'arrivals.invoice_date',
                'arrivals.vendor_id',
                // Tags count: unique TAG across items within the same invoice
                DB::raw("COUNT(DISTINCT NULLIF(TRIM(receives.tag), '')) as receives_count"),
                DB::raw('SUM(receives.qty) as total_qty'),
                DB::raw("SUM(CASE WHEN receives.qc_status = 'pass' THEN 1 ELSE 0 END) as pass_count"),
                DB::raw("SUM(CASE WHEN receives.qc_status IN ('reject','fail') THEN 1 ELSE 0 END) as fail_count"),
            ])
            ->join('incoming_arrival_items as arrival_items', 'arrival_items.arrival_id', '=', 'arrivals.id')
            ->join('incoming_receives as receives', 'receives.arrival_item_id', '=', 'arrival_items.id')
            ->join('vendors', 'vendors.id', '=', 'arrivals.vendor_id')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($builder) use ($q) {
                    $builder->where('arrivals.transaction_no', 'like', '%' . $q . '%')
                        ->orWhere('arrivals.invoice_no', 'like', '%' . $q . '%')
                        ->orWhere('vendors.vendor_name', 'like', '%' . $q . '%');
                });
            })
            ->when($flow === 'import', fn($query) => $query->whereRaw("LOWER(COALESCE(vendors.vendor_type, '')) <> 'local'"))
            ->when($flow === 'local', fn($query) => $query->whereRaw("LOWER(COALESCE(vendors.vendor_type, '')) = 'local'"))
            ->whereNull('arrivals.deleted_at')
            ->with('vendor')
            ->groupBy('arrivals.id', 'arrivals.arrival_no', 'arrivals.transaction_no', 'arrivals.invoice_no', 'arrivals.invoice_date', 'arrivals.vendor_id', 'vendors.vendor_type')
            ->orderByDesc('arrivals.created_at')
            ->paginate(10)
            ->withQueryString();

        $arrivals->getCollection()->transform(function ($arrival) {
            $arrival->loadMissing(['vendor', 'items.receives', 'containers.inspection']);
            $this->ensureCompletedArrivalTransactionNo($arrival);
            $arrival->transaction_no = Arrival::query()->whereKey($arrival->id)->value('transaction_no');
            return $arrival;
        });

        $statusCounts = Receive::select('qc_status', DB::raw('count(*) as total'))
            ->groupBy('qc_status')
            ->pluck('total', 'qc_status');

        $topVendors = Receive::select(
            'vendors.vendor_name',
            DB::raw('COUNT(incoming_receives.id) as total_receives'),
            DB::raw('SUM(incoming_receives.qty) as total_qty')
        )
            ->join('incoming_arrival_items', 'incoming_receives.arrival_item_id', '=', 'incoming_arrival_items.id')
            ->join('incoming_arrivals as arrivals', 'incoming_arrival_items.arrival_id', '=', 'arrivals.id')
            ->join('vendors', 'arrivals.vendor_id', '=', 'vendors.id')
            ->groupBy('vendors.vendor_name')
            ->orderByDesc('total_receives')
            ->limit(5)
            ->get();

        $summary = [
            'total_receives' => Receive::count(),
            'total_qty' => Receive::sum('qty'),
            'total_weight' => Receive::sum('weight'),
            'today' => Receive::whereDate('created_at', now())->count(),
        ];

        return view('receives.completed', compact('arrivals', 'statusCounts', 'topVendors', 'summary', 'q', 'flow'));
    }

    public function importDocuments(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 15;

        $filteredArrivals = $this->getCompleteImportDocumentArrivals($q, $dateFrom, $dateTo);
        $arrivals = new LengthAwarePaginator(
            $filteredArrivals->forPage($page, $perPage)->values(),
            $filteredArrivals->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $summary = [
            'total_invoices' => $filteredArrivals->count(),
            'with_pen' => $filteredArrivals->filter(fn($arrival) => filled(trim((string) ($arrival->pen_no ?? ''))))->count(),
            'with_aju' => $filteredArrivals->filter(fn($arrival) => filled(trim((string) ($arrival->aju_no ?? ''))))->count(),
        ];

        return view('receives.import_documents', compact('arrivals', 'summary', 'q', 'dateFrom', 'dateTo'));
    }

    public function exportImportDocuments(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $filename = 'rekap_no_pen_no_aju_' . now()->format('Y-m-d_His') . '.xlsx';
        $arrivals = $this->getCompleteImportDocumentArrivals($q, $dateFrom, $dateTo);

        return Excel::download(
            new ImportDocumentRecapExport($arrivals),
            $filename
        );
    }

    private function getCompleteImportDocumentArrivals(string $q = '', string $dateFrom = '', string $dateTo = '')
    {
        $arrivals = $this->buildImportDocumentsQuery($q, $dateFrom, $dateTo)->get();
        $this->syncCompletedArrivalTransactionNumbers($arrivals);

        return $arrivals
            ->filter(fn($arrival) => (bool) ($arrival->is_complete_receive ?? false))
            ->values();
    }

    private function syncCompletedArrivalTransactionNumbers($arrivals): void
    {
        collect($arrivals)->each(function ($arrival) {
            $arrival->loadMissing(['vendor', 'items.receives', 'containers.inspection']);
            $this->ensureCompletedArrivalTransactionNo($arrival);
            $arrival->transaction_no = Arrival::query()->whereKey($arrival->id)->value('transaction_no');
            $arrival->is_complete_receive = !$this->hasPendingReceives($arrival);
        });
    }

    private function buildImportDocumentsQuery(string $q = '', string $dateFrom = '', string $dateTo = '')
    {
        return Arrival::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->from('incoming_arrivals as arrivals')
            ->select([
                'arrivals.id',
                'arrivals.transaction_no',
                'arrivals.invoice_no',
                'arrivals.invoice_date',
                'arrivals.pen_no',
                'arrivals.pen_date',
                'arrivals.aju_no',
                'arrivals.vendor_id',
                'arrivals.created_at',
            ])
            ->join('vendors', 'vendors.id', '=', 'arrivals.vendor_id')
            ->whereRaw("LOWER(COALESCE(vendors.vendor_type, '')) <> 'local'")
            ->whereNull('arrivals.deleted_at')
            ->with('vendor')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($builder) use ($q) {
                    $builder->where('arrivals.transaction_no', 'like', '%' . $q . '%')
                        ->orWhere('arrivals.invoice_no', 'like', '%' . $q . '%')
                        ->orWhere('arrivals.pen_no', 'like', '%' . $q . '%')
                        ->orWhere('arrivals.aju_no', 'like', '%' . $q . '%')
                        ->orWhere('vendors.vendor_name', 'like', '%' . $q . '%');
                });
            })
            ->when($dateFrom !== '', fn($query) => $query->whereDate('arrivals.invoice_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn($query) => $query->whereDate('arrivals.invoice_date', '<=', $dateTo))
            ->orderByDesc('arrivals.invoice_date')
            ->orderByDesc('arrivals.created_at');
    }

    public function completedInvoice(Arrival $arrival)
    {
        $arrival->load(['vendor', 'items.receives', 'containers.inspection']);
        $this->ensureCompletedArrivalTransactionNo($arrival);

        $receives = Receive::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->from('incoming_receives as receives')
            ->select('receives.*')
            ->join('incoming_arrival_items as arrival_items', 'receives.arrival_item_id', '=', 'arrival_items.id')
            ->leftJoin('vendor_parts as gpv', 'gpv.id', '=', 'arrival_items.vendor_part_id')
            ->with(['arrivalItem.vendorPart', 'arrivalItem.gciPart', 'arrivalItem.arrival.vendor'])
            ->where('arrival_items.arrival_id', $arrival->id)
            ->whereNull('receives.deleted_at')
            ->orderBy('gpv.vendor_part_no', 'asc')
            ->orderByRaw('LENGTH(receives.tag) ASC')
            ->orderBy('receives.tag', 'asc')
            ->paginate(50);

        $remainingQtyTotal = collect($arrival->items)->sum(function ($item) {
            $received = $item->receives->sum('qty');
            return max(0, $item->qty_goods - $received);
        });
        $pendingItemsCount = $arrival->items->filter(function ($item) {
            $received = $item->receives->sum('qty');
            return ($item->qty_goods - $received) > 0;
        })->count();
        $hasMissingInspection = ($arrival->containers ?? collect())->isNotEmpty()
            && ($arrival->containers ?? collect())->contains(fn($c) => !$c->inspection);
        $hasMissingTag = $arrival->items
            ->flatMap(fn($i) => $i->receives ?? collect())
            ->contains(fn($r) => !is_string($r->tag) || trim($r->tag) === '');

        $hasPending = ($pendingItemsCount > 0) || $hasMissingInspection || $hasMissingTag;

        return view('receives.completed_invoice', compact(
            'arrival',
            'receives',
            'remainingQtyTotal',
            'pendingItemsCount',
            'hasPending',
            'hasMissingInspection',
            'hasMissingTag'
        ));
    }

    public function exportCompletedInvoice(Arrival $arrival)
    {
        $arrival->load(['vendor', 'items.receives', 'items.vendorPart', 'items.gciPart']);

        if ($this->hasPendingReceives($arrival)) {
            return back()->with('error', 'Invoice ini belum complete receive.');
        }

        $filenameSafe = preg_replace('/[^A-Za-z0-9_.-]+/', '-', (string) ($arrival->invoice_no ?? 'invoice'));
        $filename = 'receives_' . $filenameSafe . '_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new CompletedInvoiceReceivesExport($arrival), $filename);
    }

    public function create(ArrivalItem $arrivalItem)
    {
        $arrivalItem->load(['vendorPart.vendor', 'gciPart', 'arrival.vendor', 'arrival.containers.inspection', 'receives']);

        $totalReceived = $arrivalItem->receives->sum('qty');
        $remainingQty = max(0, $arrivalItem->qty_goods - $totalReceived);
        $totalPlanned = $arrivalItem->qty_goods;
        $defaultWeight = $arrivalItem->qty_goods > 0
            ? number_format($arrivalItem->weight_nett / $arrivalItem->qty_goods, 2, '.', '')
            : null;

        return view('receives.create', compact('arrivalItem', 'remainingQty', 'totalPlanned', 'totalReceived', 'defaultWeight'));
    }

    public function createByInvoice(Arrival $arrival)
    {
        $arrival->load(['vendor', 'containers.inspection', 'items.vendorPart', 'items.gciPart', 'items.receives']);

        $pendingItems = $arrival->items
            ->map(function ($item) {
                $totalReceived = $item->receives->sum('qty');
                $remaining = $item->qty_goods - $totalReceived;
                $item->total_received = $totalReceived;
                $item->remaining_qty = max(0, $remaining);
                $item->default_weight = $item->qty_goods > 0
                    ? number_format($item->weight_nett / $item->qty_goods, 2, '.', '')
                    : null;
                return $item;
            })
            ->filter(fn($item) => $item->remaining_qty > 0)
            ->values();

        if ($pendingItems->isEmpty()) {
            if ($this->hasPendingReceives($arrival)) {
                return redirect()
                    ->route('receives.completed.invoice', $arrival)
                    ->with('error', 'Invoice belum bisa dianggap complete: pastikan inspection container dan TAG sudah lengkap.');
            }
            return redirect()->route('receives.completed.invoice', $arrival)->with('success', 'Semua item pada invoice ini sudah diterima.');
        }

        return view('receives.invoice', [
            'arrival' => $arrival,
            'pendingItems' => $pendingItems,
        ]);
    }

    public function store(Request $request, ArrivalItem $arrivalItem)
    {
        $arrivalItem->loadMissing(['arrival.vendor']);
        $isLocal = strtolower((string) ($arrivalItem->arrival?->vendor?->vendor_type ?? '')) === 'local';

        $locationCodeRule = ['nullable', 'string', 'max:50'];
        if (Schema::hasTable('warehouse_locations')) {
            $locationCodeRule[] = Rule::exists('warehouse_locations', 'location_code');
        }

        $validated = $request->validate([
            'receive_date' => ['required', 'date'],
            'truck_no' => $isLocal ? ['required', 'string', 'max:50'] : ['nullable', 'string', 'max:50'],
            'tags' => 'required|array|min:1',
            'tags.*.tag' => 'required|string|max:255',
            'tags.*.qty' => 'required|integer|min:1',
            'tags.*.bundle_qty' => 'nullable|integer|min:0',
            'tags.*.bundle_unit' => 'required|in:PALLET,BUNDLE,BOX,BAG,ROLL,PACKAGES',
            'tags.*.location_code' => $locationCodeRule,
            // Backward compatible: old form used `weight`
            'tags.*.weight' => 'nullable|numeric',
            'tags.*.net_weight' => 'nullable|numeric',
            'tags.*.gross_weight' => 'nullable|numeric',
            'tags.*.qty_unit' => 'required|in:KGM,KG,PCS,COIL,SHEET,SET,EA',
            'tags.*.qc_status' => 'required|in:pass,reject',
        ]);

        $this->ensureTagsUniqueForArrivalItem($arrivalItem, $validated['tags'], 'tags');

        $totalRequested = collect($validated['tags'])->sum('qty');
        $totalReceived = $arrivalItem->receives()->sum('qty');
        $remainingQty = $arrivalItem->qty_goods - $totalReceived;

        if ($totalRequested > $remainingQty) {
            return back()
                ->withInput()
                ->withErrors([
                    'tags' => 'Total qty for tags (' . $totalRequested . ') exceeds remaining qty (' . $remainingQty . ').',
                ]);
        }

        $goodsUnit = strtoupper($arrivalItem->unit_goods ?? 'KGM');
        $partId = $this->resolveVendorPartId($arrivalItem);
        $gciPartId = $this->resolveGciPartId($arrivalItem);
        if ($gciPartId === null && collect($validated['tags'])->contains(fn($tag) => !empty($tag['location_code'] ?? null))) {
            $gciPartId = $this->ensurePutawayGciPartId($arrivalItem, 'tags');
        }
        $receiveAt = Carbon::parse($validated['receive_date'])->setTimeFromTimeString(now()->format('H:i:s'));
        $truckNo = isset($validated['truck_no']) && trim((string) $validated['truck_no']) !== ''
            ? strtoupper(trim((string) $validated['truck_no']))
            : null;

        // --- BOM Validation (Strict) ---
        // if ($partId) {
        //     $part = \App\Models\Part::with('gciPart')->find($partId);
        //     $gciPart = $part?->gciPart;

        //     if (!$gciPart) {
        //         // If not even linked to master, it's definitely not in BOM (unless partId IS master, but we use Vendor Part ID here)
        //         // But auto-linking should have handled this?
        //         // For now, if no GCI link, we can't validate BOM.
        //         // Should we block? User said: "barang yg belom ada di BOM ... di block".
        //         // If it has no internal ID, it's an orphan vendor part.
        //         throw \Illuminate\Validation\ValidationException::withMessages([
        //             'tags' => "Part ini ({$part->part_no}) belum terdaftar di GCI Master Part. Harap hubungi Engineering/Admin.",
        //         ]);
        //     }

        //     // Check if GCI Part is used in any BOM
        //     if (!$gciPart->componentUsages()->exists()) {
        //         throw \Illuminate\Validation\ValidationException::withMessages([
        //             'tags' => "Part ini ({$gciPart->part_no} / {$part->part_no}) BELUM TERDAFTAR di Bill of Material (BOM) manapun. Receiving DITOLAK.",
        //         ]);
        //     }
        // }
        // -------------------------------

        DB::transaction(function () use ($validated, $arrivalItem, $goodsUnit, $partId, $gciPartId, $receiveAt, $truckNo) {
            $locationAdds = []; // key: "locationCode|tag"
            foreach ($validated['tags'] as $tagData) {
                if (strtoupper($tagData['qty_unit']) !== $goodsUnit) {
                    throw new HttpResponseException(back()->withInput()->withErrors([
                        'tags' => "Unit qty tidak sesuai. Item ini menggunakan unit {$goodsUnit}.",
                    ]));
                }

                $netWeight = $tagData['net_weight'] ?? $tagData['weight'] ?? null;
                if ($netWeight === null && $goodsUnit === 'KGM') {
                    $netWeight = $tagData['qty'];
                }

                $locationCode = null;
                if (array_key_exists('location_code', $tagData)) {
                    $locationCode = strtoupper(trim((string) $tagData['location_code']));
                    if ($locationCode === '') {
                        $locationCode = null;
                    }
                }

                $tag = $this->normalizeTag($tagData['tag'] ?? null);

                $receive = $arrivalItem->receives()->create([
                    'tag' => $tag,
                    'qty' => $tagData['qty'],
                    'bundle_unit' => $tagData['bundle_unit'] ?? null,
                    'bundle_qty' => $tagData['bundle_qty'] ?? 0,
                    // Keep `weight` for existing reporting, mirror from net_weight
                    'weight' => $netWeight,
                    'net_weight' => $netWeight,
                    'gross_weight' => $tagData['gross_weight'] ?? null,
                    'qty_unit' => $goodsUnit,
                    'ata_date' => $receiveAt,
                    'qc_status' => $tagData['qc_status'] ?? 'pass',
                    'jo_po_number' => null,
                    'truck_no' => $truckNo,
                    'location_code' => $locationCode,
                ]);
                $tag = $this->resolveReceiveTag($tagData['tag'] ?? null, (int) $receive->id, $receiveAt);
                if ($tag !== null && $receive->tag !== $tag) {
                    $receive->update(['tag' => $tag]);
                }

                if (($tagData['qc_status'] ?? 'pass') === 'pass') {
                    $addQty = $goodsUnit === 'COIL' ? (float) ($netWeight ?? 0) : (float) $tagData['qty'];
                    if ($locationCode) {
                        $key = $locationCode . '|' . ($tag ?? '');
                        $locationAdds[$key] = ($locationAdds[$key] ?? ['location' => $locationCode, 'tag' => $tag, 'qty' => 0]);
                        $locationAdds[$key]['qty'] += $addQty;
                    }
                }
            }


            // Putaway: update stock per location+tag (pass only).
            if (!empty($locationAdds) && $partId) {
                foreach ($locationAdds as $entry) {
                    if ($entry['qty'] > 0) {
                        InventoryLocationStock::updateStock(
                            $gciPartId,
                            $entry['location'],
                            (float) $entry['qty'],
                            $entry['tag'],
                            $entry['tag'],
                            'RECEIVE',
                            null,
                            null,
                            $arrivalItem->arrival_id,
                            $arrivalItem->arrival?->invoice_no,
                            null,
                            null,
                            null
                        );
                    }
                }
            }
        });

        $this->logActivity('STORE Receive', "arrival_item_id:{$arrivalItem->id}", [
            'total_tags' => count($validated['tags']),
            'total_qty' => collect($validated['tags'])->sum('qty'),
            'truck_no' => $truckNo,
        ]);

        $arrival = $arrivalItem->arrival()->with('items.receives')->first();
        if ($arrival) {
            $isComplete = !$this->hasPendingReceives($arrival);
            if ($isComplete && empty($arrival->transaction_no)) {
                $arrival->transaction_no = Arrival::generateTransactionNo($receiveAt->toDateString());
                $arrival->save();
            }
            $message = $isComplete
                ? 'Invoice sudah complete receive. Transaction No: ' . $arrival->transaction_no
                : 'TAG tersimpan. Silakan cek summary (masih ada pending).';
            return redirect()->route('receives.completed.invoice', $arrival)->with('success', $message);
        }

        return redirect()->route('receives.index')->with('success', 'TAG tersimpan.');
    }

    public function storeByInvoice(Request $request, Arrival $arrival)
    {
        $arrival->loadMissing(['vendor', 'items.receives']);
        $isLocal = strtolower((string) ($arrival->vendor?->vendor_type ?? '')) === 'local';

        $tagMode = $isLocal ? (string) $request->input('tag_mode', 'no_tag') : 'with_tag';
        if (!in_array($tagMode, ['with_tag', 'no_tag'], true)) {
            $tagMode = $isLocal ? 'no_tag' : 'with_tag';
        }

        $locationCodeRule = ['nullable', 'string', 'max:50'];
        if (Schema::hasTable('warehouse_locations')) {
            $locationCodeRule[] = Rule::exists('warehouse_locations', 'location_code');
        }

        $rules = [
            'receive_date' => ['required', 'date'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
            'delivery_note_no' => ['nullable', 'string', 'max:100'],
            'truck_no' => $isLocal ? ['required', 'string', 'max:50'] : ['nullable', 'string', 'max:50'],
            'tag_mode' => $isLocal ? ['required', 'in:with_tag,no_tag'] : ['nullable'],
            'delivery_note_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'packing_list_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'items' => ['required', 'array', 'min:1'],
        ];

        if ($tagMode === 'no_tag') {
            $rules += [
                'items.*.summary' => ['nullable', 'array'],
                'items.*.summary.qty' => ['nullable', 'integer', 'min:0'],
                'items.*.summary.bundle_qty' => ['nullable', 'integer', 'min:0'],
                'items.*.summary.bundle_unit' => ['nullable', 'in:PALLET,BUNDLE,BOX,BAG,ROLL,PACKAGES'],
                'items.*.summary.location_code' => $locationCodeRule,
                'items.*.summary.net_weight' => ['nullable', 'numeric'],
                'items.*.summary.gross_weight' => ['nullable', 'numeric'],
                'items.*.summary.qty_unit' => ['nullable', 'in:KGM,KG,PCS,COIL,SHEET,SET,EA,UOM,ROLL'],
                'items.*.summary.qc_status' => ['nullable', 'in:pass,reject'],
            ];
        } else {
            $rules += [
                'items.*.tags' => ['nullable', 'array'],
                'items.*.tags.*.tag' => ['required_with:items.*.tags', 'string', 'max:255'],
                'items.*.tags.*.qty' => ['required_with:items.*.tags', 'integer', 'min:1'],
                'items.*.tags.*.bundle_qty' => ['nullable', 'integer', 'min:0'],
                'items.*.tags.*.bundle_unit' => ['required_with:items.*.tags', 'in:PALLET,BUNDLE,BOX,BAG,ROLL,PACKAGES'],
                'items.*.tags.*.location_code' => $locationCodeRule,
                // Backward compatible: old form used `weight`
                'items.*.tags.*.weight' => ['nullable', 'numeric'],
                'items.*.tags.*.net_weight' => ['nullable', 'numeric'],
                'items.*.tags.*.gross_weight' => ['nullable', 'numeric'],
                'items.*.tags.*.qty_unit' => ['required_with:items.*.tags', 'in:KGM,KG,PCS,COIL,SHEET,SET,EA,UOM'],
                'items.*.tags.*.qc_status' => ['required_with:items.*.tags', 'in:pass,reject'],
            ];
        }

        $validated = $request->validate($rules);

        $itemsInput = [];
        if ($tagMode === 'no_tag') {
            foreach (($validated['items'] ?? []) as $itemId => $itemData) {
                $summary = is_array($itemData['summary'] ?? null) ? $itemData['summary'] : null;
                $qty = (int) ($summary['qty'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $itemsInput[$itemId] = [
                    'tags' => [
                        [
                            'tag' => null,
                            'qty' => $qty,
                            'bundle_qty' => (int) ($summary['bundle_qty'] ?? 0),
                            'bundle_unit' => $summary['bundle_unit'] ?? null,
                            'location_code' => $summary['location_code'] ?? null,
                            'net_weight' => $summary['net_weight'] ?? null,
                            'gross_weight' => $summary['gross_weight'] ?? null,
                            'qty_unit' => $summary['qty_unit'] ?? null,
                            'qc_status' => $summary['qc_status'] ?? 'pass',
                        ]
                    ],
                ];
            }
        } else {
            $itemsInput = collect($validated['items'])
                ->filter(fn($item) => !empty($item['tags']))
                ->all();
        }

        if (empty($itemsInput)) {
            return back()->withErrors([
                'items' => $tagMode === 'no_tag'
                    ? 'Isi minimal satu item (qty) untuk receive.'
                    : 'Tambah minimal satu tag pada salah satu item.',
            ])->withInput();
        }

        foreach ($itemsInput as $itemId => $itemData) {
            $arrivalItem = $arrival->items->firstWhere('id', $itemId);
            if (!$arrivalItem) {
                continue;
            }
            $this->ensureTagsUniqueForArrivalItem($arrivalItem, $itemData['tags'] ?? [], "items.$itemId.tags");
        }

        foreach ($itemsInput as $itemId => $itemData) {
            $arrivalItem = $arrival->items->firstWhere('id', $itemId);
            if (!$arrivalItem) {
                return back()->withErrors(['items' => 'Item tidak ditemukan pada invoice ini.'])->withInput();
            }

            $totalRequested = collect($itemData['tags'])->sum('qty');
            $totalReceived = $arrivalItem->receives->sum('qty');
            $remainingQty = $arrivalItem->qty_goods - $totalReceived;

            if ($totalRequested > $remainingQty) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "items.$itemId.tags" => "Total qty untuk item {$arrivalItem->vendorPart->vendor_part_no} ({$totalRequested}) melebihi sisa ({$remainingQty}).",
                    ]);
            }
        }

        $locationAdds = [];
        $receiveAt = Carbon::parse($validated['receive_date'])->setTimeFromTimeString(now()->format('H:i:s'));
        $truckNo = isset($validated['truck_no']) && trim((string) $validated['truck_no']) !== ''
            ? strtoupper(trim((string) $validated['truck_no']))
            : null;

        DB::transaction(function () use ($itemsInput, $arrival, $receiveAt, $truckNo, &$locationAdds, $request, $validated) {
            foreach ($itemsInput as $itemId => $itemData) {
                $arrivalItem = $arrival->items->firstWhere('id', $itemId);
                $goodsUnit = strtoupper($arrivalItem->unit_goods ?? 'KGM');

                // --- BOM Validation (Strict) ---
                // $partId = (int) $arrivalItem->part_id;
                // if ($partId) {
                //     $part = \App\Models\Part::with('gciPart')->find($partId);
                //     $gciPart = $part?->gciPart;

                //     if (!$gciPart) {
                //         throw \Illuminate\Validation\ValidationException::withMessages([
                //             "items.$itemId.tags" => "Part ini ({$part->part_no}) belum terdaftar di GCI Master Part.",
                //         ]);
                //     }
                //     if (!$gciPart->componentUsages()->exists()) {
                //         throw \Illuminate\Validation\ValidationException::withMessages([
                //             "items.$itemId.tags" => "Part ini ({$gciPart->part_no}) BELUM TERDAFTAR di BOM manapun. Receiving DITOLAK.",
                //         ]);
                //     }
                // }
                // -------------------------------

                foreach ($itemData['tags'] as $tagData) {
                    if (strtoupper($tagData['qty_unit'] ?? '') !== $goodsUnit) {
                        throw new HttpResponseException(back()->withInput()->withErrors([
                            "items.$itemId.tags" => "Unit qty tidak sesuai. Item ini menggunakan unit {$goodsUnit}.",
                        ]));
                    }

                    $netWeight = $tagData['net_weight'] ?? $tagData['weight'] ?? null;
                    if ($netWeight === null && $goodsUnit === 'KGM') {
                        $netWeight = $tagData['qty'];
                    }
                    $locationCode = null;
                    if (array_key_exists('location_code', $tagData)) {
                        $locationCode = strtoupper(trim((string) $tagData['location_code']));
                        if ($locationCode === '') {
                            $locationCode = null;
                        }
                    }
                    $receive = $arrivalItem->receives()->create([
                        'tag' => $this->normalizeTag($tagData['tag'] ?? null),
                        'qty' => $tagData['qty'],
                        'bundle_unit' => $tagData['bundle_unit'] ?? null,
                        'bundle_qty' => $tagData['bundle_qty'] ?? 0,
                        // Keep `weight` for existing reporting, mirror from net_weight
                        'weight' => $netWeight,
                        'net_weight' => $netWeight,
                        'gross_weight' => $tagData['gross_weight'] ?? null,
                        'qty_unit' => $goodsUnit,
                        'ata_date' => $receiveAt,
                        'qc_status' => $tagData['qc_status'] ?? 'pass',
                        'jo_po_number' => null,
                        'invoice_no' => $validated['invoice_no'] ?? null,
                        'delivery_note_no' => $validated['delivery_note_no'] ?? null,
                        'truck_no' => $truckNo,
                        'location_code' => $locationCode,
                    ]);
                    $tag = $this->resolveReceiveTag($tagData['tag'] ?? null, (int) $receive->id, $receiveAt);
                    if ($tag !== null && $receive->tag !== $tag) {
                        $receive->update(['tag' => $tag]);
                    }

                    if (($tagData['qc_status'] ?? 'pass') === 'pass') {
                        $addQty = $goodsUnit === 'COIL' ? (float) ($netWeight ?? 0) : (float) $tagData['qty'];
                        if ($locationCode) {
                            $key = $locationCode . '|' . ($tag ?? '');
                            $partId = $this->resolveVendorPartId($arrivalItem);
                            $locationAdds[$partId][$key] = ($locationAdds[$partId][$key] ?? ['location' => $locationCode, 'tag' => $tag, 'qty' => 0]);
                            $locationAdds[$partId][$key]['qty'] += $addQty;
                        }
                    }
                }
            }


            // Putaway: update stock per location+tag (pass only).
            foreach ($locationAdds as $partId => $byLocation) {
                if (!$partId || empty($byLocation) || !is_array($byLocation)) {
                    continue;
                }
                $gciPartId = $this->ensurePutawayGciPartId($arrivalItem, "items.$itemId.tags");
                foreach ($byLocation as $entry) {
                    if ($entry['qty'] > 0) {
                        InventoryLocationStock::updateStock(
                            $gciPartId,
                            $entry['location'],
                            (float) $entry['qty'],
                            $entry['tag'],
                            $entry['tag'],
                            'RECEIVE',
                            null,
                            null,
                            $arrival->id,
                            $arrival->invoice_no,
                            null,
                            null,
                            null
                        );
                    }
                }
            }
            $dir = "local_pos/arrival-{$arrival->id}";

            if ($request->hasFile('delivery_note_file')) {
                $file = $request->file('delivery_note_file');
                $path = $file->storePubliclyAs($dir, "surat_jalan." . $file->getClientOriginalExtension(), 'public');
                $arrival->delivery_note_file = $path;
                $arrival->save();
            }

            if ($request->hasFile('invoice_file')) {
                $file = $request->file('invoice_file');
                $path = $file->storePubliclyAs($dir, "invoice." . $file->getClientOriginalExtension(), 'public');
                $arrival->invoice_file = $path;
                $arrival->save();
            }

            if ($request->hasFile('packing_list_file')) {
                $file = $request->file('packing_list_file');
                $path = $file->storePubliclyAs($dir, "packing_list." . $file->getClientOriginalExtension(), 'public');
                $arrival->packing_list_file = $path;
                $arrival->save();
            }
        });

        $this->logActivity('STORE Receive (Invoice)', "arrival_id:{$arrival->id} invoice:{$arrival->invoice_no}", [
            'items_count' => count($itemsInput),
            'total_qty' => collect($itemsInput)->sum(fn($i) => collect($i['tags'])->sum('qty')),
            'truck_no' => $truckNo,
        ]);

        $arrival->load('items.receives');
        $isComplete = !$this->hasPendingReceives($arrival);
        if ($isComplete && empty($arrival->transaction_no)) {
            $arrival->transaction_no = Arrival::generateTransactionNo($receiveAt->toDateString());
            $arrival->save();
        }
        $message = $isComplete
            ? 'Invoice sudah complete receive. Transaction No: ' . $arrival->transaction_no
            : 'TAG tersimpan. Silakan cek summary (masih ada pending).';

        return redirect()->route('receives.completed.invoice', $arrival)->with('success', $message);
    }

    public function printLabel(Receive $receive)
    {
        $receive->load([
            'arrivalItem.vendorPart.gciPart',
            'arrivalItem.gciPart',
            'arrivalItem.arrival.vendor',
        ]);
        $arrivalItem = $receive->arrivalItem;
        $arrival = $arrivalItem?->arrival;
        $vendorPart = $arrivalItem?->vendorPart;

        $receivedAt = $receive->ata_date ?? now();
        $monthNumber = (int) $receivedAt->format('m');

        $warehouseLocation = null;
        $locCode = is_string($receive->location_code) ? strtoupper(trim($receive->location_code)) : '';
        if ($locCode !== '' && Schema::hasTable('warehouse_locations')) {
            $warehouseLocation = WarehouseLocation::query()->where('location_code', $locCode)->first();
        }

        // Update QR Payload to JSON (Unified Standard)
        // This ensures the App receives Part No and GCI No for accurate inventory tracking.
        $resolvedTag = $receive->ensureSystemTag();
        $payload = [
            'tag' => $resolvedTag,
            'receive_id' => (int) $receive->id,
            'part_id' => (int) ($vendorPart?->id ?? 0),
            'gci_part_id' => (int) ($vendorPart?->gci_part_id ?? 0),
            'part_no' => (string) ($vendorPart?->vendor_part_no ?? ''),
            'gci_part_no' => (string) ($vendorPart?->gciPart?->part_no ?? ''),
            'part_name' => (string) ($vendorPart?->vendor_part_name ?? ''),
            'qty' => (float) $receive->qty,
            'qty_unit' => (string) ($receive->qty_unit ?? ''),
            'invoice' => (string) ($arrival?->invoice_no ?? '-'),
            'delivery_note_no' => (string) ($receive->delivery_note_no ?? $arrival?->sj_no ?? ''),
            'location_code' => (string) ($receive->location_code ?? ''),
            'vendor_type' => (string) ($arrival?->vendor?->vendor_type ?? ''),
        ];

        $payloadString = json_encode($payload);

        $qrSvg = QrSvg::make($payloadString, 400, 0);

        return view('receives.label', compact('receive', 'qrSvg', 'monthNumber', 'warehouseLocation'));
    }

    public function edit(Receive $receive)
    {
        $receive->load(['arrivalItem.vendorPart', 'arrivalItem.gciPart', 'arrivalItem.arrival.vendor']);

        return view('receives.edit', [
            'receive' => $receive,
            'arrival' => $receive->arrivalItem->arrival,
            'arrivalItem' => $receive->arrivalItem,
        ]);
    }

    public function update(Request $request, Receive $receive)
    {
        $receive->load(['arrivalItem.arrival', 'arrivalItem.vendorPart', 'arrivalItem.gciPart']);
        $arrivalItem = $receive->arrivalItem;
        $arrival = $arrivalItem->arrival;
        $isLocal = strtolower((string) ($arrival->vendor?->vendor_type ?? '')) === 'local';

        $goodsUnit = strtoupper($arrivalItem->unit_goods ?? 'KGM');

        $locationCodeRule = ['nullable', 'string', 'max:50'];
        if (Schema::hasTable('warehouse_locations')) {
            $locationCodeRule[] = Rule::exists('warehouse_locations', 'location_code');
        }

        $validated = $request->validate([
            'receive_date' => ['required', 'date'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
            'delivery_note_no' => ['nullable', 'string', 'max:100'],
            'tag' => ['nullable', 'string', 'max:255'],
            'location_code' => $locationCodeRule,
            'truck_no' => $isLocal ? ['required', 'string', 'max:50'] : ['nullable', 'string', 'max:50'],
            'bundle_qty' => ['nullable', 'integer', 'min:0'],
            'bundle_unit' => ['required', 'in:PALLET,BUNDLE,BOX,BAG,ROLL,PACKAGES'],
            'qty' => ['required', 'integer', 'min:1'],
            'net_weight' => ['nullable', 'numeric'],
            'gross_weight' => ['nullable', 'numeric'],
            'qc_status' => ['required', 'in:pass,reject'],
        ]);

        $tag = $this->resolveReceiveTag($validated['tag'] ?? null, (int) $receive->id, $receive->ata_date);
        $locationCode = array_key_exists('location_code', $validated)
            ? strtoupper(trim((string) $validated['location_code']))
            : null;
        if ($locationCode === '') {
            $locationCode = null;
        }

        if (($validated['net_weight'] ?? null) !== null && ($validated['gross_weight'] ?? null) !== null) {
            if ((float) $validated['net_weight'] > (float) $validated['gross_weight']) {
                return back()->withInput()->withErrors([
                    'net_weight' => 'Net weight harus lebih kecil atau sama dengan gross weight.',
                ]);
            }
        }

        // Check tag uniqueness within this arrival item (ignore current receive).
        if ($tag !== null) {
            $this->ensureTagsUniqueForArrivalItem(
                $arrivalItem,
                [['tag' => $tag]],
                'tag',
                (int) $receive->id
            );
        }

        $receiveAt = Carbon::parse($validated['receive_date'])->setTimeFromTimeString(now()->format('H:i:s'));

        $oldLocationCode = is_string($receive->location_code) ? strtoupper(trim((string) $receive->location_code)) : '';
        if ($oldLocationCode === '') {
            $oldLocationCode = null;
        }

        $oldQty = (float) $receive->qty;
        $oldPass = $receive->qc_status === 'pass';
        $oldContribution = $oldPass
            ? ($goodsUnit === 'COIL'
                ? (float) ($receive->net_weight ?? $receive->weight ?? $receive->qty ?? 0)
                : $oldQty)
            : 0.0;

        $newQty = (float) $validated['qty'];
        $newPass = $validated['qc_status'] === 'pass';
        $newContribution = $newPass
            ? ($goodsUnit === 'COIL'
                ? (float) ($validated['net_weight'] ?? $validated['weight'] ?? $validated['qty'] ?? 0)
                : $newQty)
            : 0.0;

        $delta = $newContribution - $oldContribution;

        DB::transaction(function () use ($receive, $arrivalItem, $goodsUnit, $validated, $tag, $locationCode, $oldLocationCode, $oldContribution, $newContribution, $receiveAt, $delta) {
            $truckNo = isset($validated['truck_no']) && trim((string) $validated['truck_no']) !== ''
                ? strtoupper(trim((string) $validated['truck_no']))
                : null;

            // Update receive row
            $receive->update([
                'tag' => $tag,
                'qty' => (int) $validated['qty'],
                'bundle_unit' => $validated['bundle_unit'],
                'bundle_qty' => (int) ($validated['bundle_qty'] ?? 0),
                'weight' => $validated['net_weight'] ?? $validated['weight'] ?? null,
                'net_weight' => $validated['net_weight'] ?? $validated['weight'] ?? null,
                'gross_weight' => $validated['gross_weight'] ?? null,
                'qty_unit' => $goodsUnit,
                'ata_date' => $receiveAt,
                'qc_status' => $validated['qc_status'],
                'invoice_no' => $validated['invoice_no'] ?? null,
                'delivery_note_no' => $validated['delivery_note_no'] ?? null,
                'truck_no' => $truckNo,
                'location_code' => $locationCode,
            ]);

            // Keep location stock consistent with pass qty.
            $partId = $this->resolveVendorPartId($arrivalItem);
            $gciPartId = $this->resolveGciPartId($arrivalItem);
            if ($partId) {
                if ($oldLocationCode && $oldContribution > 0) {
                    InventoryLocationStock::updateStock(
                        $gciPartId,
                        $oldLocationCode,
                        -$oldContribution,
                        $tag,
                        $tag,
                        'RECEIVE',
                        null,
                        $receive->id,
                        $arrivalItem->arrival_id,
                        $receive->invoice_no ?: $arrivalItem->arrival?->invoice_no,
                        null,
                        null,
                        null
                    );
                }
                if ($locationCode && $newContribution > 0) {
                    InventoryLocationStock::updateStock(
                        $gciPartId,
                        $locationCode,
                        $newContribution,
                        $tag,
                        $tag,
                        'RECEIVE',
                        null,
                        $receive->id,
                        $arrivalItem->arrival_id,
                        $validated['invoice_no'] ?? $arrivalItem->arrival?->invoice_no,
                        null,
                        null,
                        null
                    );
                }
            }

        });

        $this->logActivity('UPDATE Receive', "receive_id:{$receive->id}", [
            'old' => ['qty' => $oldQty, 'qc_status' => $oldPass ? 'pass' : 'reject', 'location' => $oldLocationCode],
            'new' => ['qty' => $newQty, 'qc_status' => $validated['qc_status'], 'location' => $locationCode],
            'inventory_delta' => $delta,
        ]);

        return redirect()
            ->route('receives.completed.invoice', $arrival)
            ->with('success', 'Receive berhasil diupdate.');
    }

    public function destroy(Receive $receive)
    {
        $receive->load(['arrivalItem.arrival', 'arrivalItem.vendorPart', 'arrivalItem.gciPart']);
        $arrivalItem = $receive->arrivalItem;
        $arrival = $arrivalItem->arrival;
        $goodsUnit = strtoupper($arrivalItem->unit_goods ?? 'KGM');

        $locationCode = is_string($receive->location_code) ? strtoupper(trim((string) $receive->location_code)) : '';
        if ($locationCode === '') {
            $locationCode = null;
        }

        $contribution = $receive->qc_status === 'pass'
            ? ($goodsUnit === 'COIL'
                ? (float) ($receive->net_weight ?? $receive->weight ?? $receive->qty ?? 0)
                : (float) ($receive->qty ?? 0))
            : 0.0;

        DB::transaction(function () use ($receive, $arrivalItem, $arrival, $locationCode, $contribution) {
            $partId = $this->resolveVendorPartId($arrivalItem);
            $gciPartId = $this->resolveGciPartId($arrivalItem);

            if ($partId && $contribution > 0 && $locationCode) {
                InventoryLocationStock::updateStock(
                    $gciPartId,
                    $locationCode,
                    -$contribution,
                    $receive->tag,
                    $receive->tag,
                    'RECEIVE_DELETE',
                    null,
                    $receive->id,
                    $arrivalItem->arrival_id,
                    $receive->invoice_no ?: $arrival?->invoice_no,
                    null,
                    null,
                    null
                );
            }

            $receive->delete();
        });

        $this->logActivity('DELETE Receive', "receive_id:{$receive->id}", [
            'tag' => $receive->tag,
            'qty' => $receive->qty,
            'qc_status' => $receive->qc_status,
            'inventory_delta' => -$contribution,
        ]);

        return redirect()
            ->route('receives.completed.invoice', $arrival)
            ->with('success', "Receive {$receive->tag} berhasil dihapus.");
    }
}
