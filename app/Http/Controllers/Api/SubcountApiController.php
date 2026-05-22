<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubconOrder;
use App\Models\SubcountBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubcountApiController extends Controller
{
    public function whToSend(Request $request)
    {
        $query = SubconOrder::query()
            ->with([
                'vendor:id,vendor_name',
                'rmPart:id,part_no,part_name,net_weight',
                'gciPart:id,part_no,part_name,net_weight',
                'bomItem.consumptionUom',
                'bomItem.wipUom',
            ])
            ->withCount('subcountBatches')
            ->whereIn('status', ['sent', 'partial'])
            ->whereRaw('(qty_sent - qty_received - qty_rejected) > 0')
            ->orderByDesc('sent_date')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $search = trim((string) $request->query('q'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('order_no', 'like', "%{$search}%")
                    ->orWhere('contract_no', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('vendor_name', 'like', "%{$search}%"))
                    ->orWhereHas('rmPart', fn ($partQuery) => $partQuery
                        ->where('part_no', 'like', "%{$search}%")
                        ->orWhere('part_name', 'like', "%{$search}%"))
                    ->orWhereHas('gciPart', fn ($partQuery) => $partQuery
                        ->where('part_no', 'like', "%{$search}%")
                        ->orWhere('part_name', 'like', "%{$search}%"));
            });
        }

        $orders = $query
            ->limit(min((int) $request->integer('limit', 100), 200))
            ->get()
            ->map(function (SubconOrder $order) {
                $outstanding = max(0, (int) $order->qty_sent - (int) $order->qty_received - (int) $order->qty_rejected);
                $rmPart = $order->rmPart;
                $wipPart = $order->gciPart;

                return [
                    'id' => (int) $order->id,
                    'order_no' => $order->order_no,
                    'contract_no' => $order->contract_no,
                    'vendor_name' => $order->vendor?->vendor_name,
                    'sent_date' => optional($order->sent_date)->toDateString(),
                    'expected_return_date' => optional($order->expected_return_date)->toDateString(),
                    'status' => $order->status,
                    'process_type' => $order->process_type,
                    'send_location_code' => $order->send_location_code,
                    'qty_sent' => (int) $order->qty_sent,
                    'qty_received' => (int) $order->qty_received,
                    'qty_rejected' => (int) $order->qty_rejected,
                    'qty_outstanding' => $outstanding,
                    'weight_kgm' => (float) ($order->weight_kgm ?? 0),
                    'uom' => strtoupper((string) ($order->bomItem?->wipUom?->uom_code
                        ?? $order->bomItem?->consumptionUom?->uom_code
                        ?? 'PCS')),
                    'rm_part_no' => $rmPart?->part_no,
                    'rm_part_name' => $rmPart?->part_name,
                    'wip_part_no' => $wipPart?->part_no,
                    'wip_part_name' => $wipPart?->part_name,
                    'title' => trim(implode(' - ', array_filter([
                        $order->order_no,
                        $order->vendor?->vendor_name,
                    ]))),
                    'part_info' => trim(implode(' / ', array_filter([
                        $rmPart?->part_no,
                        $rmPart?->part_name,
                        $wipPart?->part_no,
                        $wipPart?->part_name,
                    ]))),
                    'description' => $order->notes,
                    'subcount_upload_count' => (int) $order->subcount_batches_count,
                ];
            });

        return response()->json([
            'ok' => true,
            'data' => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => ['nullable', 'string', 'max:100'],
            'subcon_order_id' => ['nullable', 'integer', 'exists:subcon_orders,id'],
            'subcon_order_no' => ['nullable', 'string', 'max:100'],
            'subcount_no' => ['required', 'string', 'max:100'],
            'created_at' => ['nullable', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'part_info' => ['nullable', 'string', 'max:255'],
            'operator_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'total_net_weight_kg' => ['nullable', 'numeric'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.id' => ['nullable', 'string', 'max:100'],
            'records.*.created_at' => ['nullable', 'date'],
            'records.*.packaging_id' => ['required', 'string', 'max:100'],
            'records.*.packaging_type' => ['nullable', 'string', 'max:255'],
            'records.*.packaging_weight_kg' => ['required', 'numeric'],
            'records.*.gross_weight_kg' => ['required', 'numeric'],
            'records.*.net_item_weight_kg' => ['required', 'numeric'],
            'records.*.description' => ['nullable', 'string'],
            'records.*.packaging_photo.base64' => ['required', 'string'],
            'records.*.packaging_photo.file_name' => ['nullable', 'string', 'max:255'],
            'records.*.packaging_photo.mime_type' => ['nullable', 'string', 'max:100'],
            'records.*.gross_photo.base64' => ['required', 'string'],
            'records.*.gross_photo.file_name' => ['nullable', 'string', 'max:255'],
            'records.*.gross_photo.mime_type' => ['nullable', 'string', 'max:100'],
        ]);

        $batch = DB::transaction(function () use ($validated, $request) {
            $subconOrder = $this->resolveSubconOrder($validated);

            $batch = SubcountBatch::updateOrCreate(
                ['subcount_no' => $validated['subcount_no']],
                [
                    'external_id' => $validated['id'] ?? null,
                    'subcon_order_id' => $subconOrder?->id,
                    'subcon_order_no' => $subconOrder?->order_no ?? ($validated['subcon_order_no'] ?? null),
                    'created_at_mobile' => $validated['created_at'] ?? null,
                    'received_at' => now(),
                    'title' => $validated['title'],
                    'part_info' => $validated['part_info'] ?? null,
                    'operator_name' => $validated['operator_name'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'total_net_weight_kg' => $validated['total_net_weight_kg'] ?? collect($validated['records'])->sum('net_item_weight_kg'),
                    'raw_payload' => $request->all(),
                ]
            );

            $batch->records()->delete();

            foreach ($validated['records'] as $index => $record) {
                $recordId = $record['id'] ?? (string) ($index + 1);
                $packagingPhotoPath = $this->storeBase64Photo(
                    $record['packaging_photo'],
                    $validated['subcount_no'],
                    $recordId,
                    'packaging'
                );
                $grossPhotoPath = $this->storeBase64Photo(
                    $record['gross_photo'],
                    $validated['subcount_no'],
                    $recordId,
                    'gross'
                );

                $batch->records()->create([
                    'external_id' => $record['id'] ?? null,
                    'created_at_mobile' => $record['created_at'] ?? null,
                    'packaging_id' => $record['packaging_id'],
                    'packaging_type' => $record['packaging_type'] ?? null,
                    'packaging_weight_kg' => $record['packaging_weight_kg'],
                    'gross_weight_kg' => $record['gross_weight_kg'],
                    'net_item_weight_kg' => $record['net_item_weight_kg'],
                    'description' => $record['description'] ?? null,
                    'packaging_photo_path' => $packagingPhotoPath,
                    'gross_photo_path' => $grossPhotoPath,
                ]);
            }

            return $batch->fresh('records');
        });

        return response()->json([
            'ok' => true,
            'id' => $batch->id,
            'reference' => $batch->subcount_no,
            'records_count' => $batch->records->count(),
        ], 201);
    }

    private function resolveSubconOrder(array $validated): ?SubconOrder
    {
        if (!empty($validated['subcon_order_id'])) {
            return SubconOrder::query()->find((int) $validated['subcon_order_id']);
        }

        if (!empty($validated['subcon_order_no'])) {
            return SubconOrder::query()->where('order_no', $validated['subcon_order_no'])->first();
        }

        return null;
    }

    private function storeBase64Photo(array $photo, string $subcountNo, string $recordId, string $kind): string
    {
        $base64 = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', $photo['base64']);
        $bytes = base64_decode($base64, true);

        if ($bytes === false) {
            throw new \RuntimeException("Foto {$kind} tidak valid.");
        }

        $extension = $this->resolveExtension($photo['file_name'] ?? null, $photo['mime_type'] ?? null);
        $fileName = implode('-', [
            Str::slug($subcountNo) ?: 'subcount',
            Str::slug($recordId) ?: 'record',
            $kind,
        ]) . '.' . $extension;

        $path = 'subcounts/' . $fileName;
        Storage::disk('public')->put($path, $bytes);

        return $path;
    }

    private function resolveExtension(?string $fileName, ?string $mimeType): string
    {
        $extension = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return $extension;
        }

        return match ($mimeType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }
}
