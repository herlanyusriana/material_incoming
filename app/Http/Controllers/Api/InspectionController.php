<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arrival;
use App\Models\ArrivalInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InspectionController extends Controller
{
    public function pending(Request $request)
    {
        $arrivals = Arrival::query()
            ->with(['vendor'])
            ->whereDoesntHave('inspection')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Arrival $arrival) => $this->arrivalPayload($arrival));

        return response()->json(['data' => $arrivals]);
    }

    public function show(Arrival $arrival)
    {
        $arrival->load(['vendor', 'inspection']);

        return response()->json([
            'arrival' => $this->arrivalPayload($arrival),
            'inspection' => $arrival->inspection ? $this->inspectionPayload($arrival->inspection) : null,
        ]);
    }

    public function upsert(Request $request, Arrival $arrival)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['ok', 'damage'])],
            'notes' => ['nullable', 'string'],
            'issues_left' => ['nullable', 'array'],
            'issues_left.*' => ['string', 'max:50'],
            'issues_right' => ['nullable', 'array'],
            'issues_right.*' => ['string', 'max:50'],
            'issues_front' => ['nullable', 'array'],
            'issues_front.*' => ['string', 'max:50'],
            'issues_back' => ['nullable', 'array'],
            'issues_back.*' => ['string', 'max:50'],
            'photo_left' => ['nullable', 'image', 'max:10240'],
            'photo_right' => ['nullable', 'image', 'max:10240'],
            'photo_front' => ['nullable', 'image', 'max:10240'],
            'photo_back' => ['nullable', 'image', 'max:10240'],
        ]);

        $inspection = ArrivalInspection::firstOrNew(['arrival_id' => $arrival->id]);
        $inspection->fill([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'issues_left' => $validated['issues_left'] ?? [],
            'issues_right' => $validated['issues_right'] ?? [],
            'issues_front' => $validated['issues_front'] ?? [],
            'issues_back' => $validated['issues_back'] ?? [],
            'inspected_by' => $request->user()?->id,
        ]);

        $dir = "inspections/{$arrival->id}";
        foreach (['left', 'right', 'front', 'back'] as $side) {
            $key = "photo_{$side}";
            if (!$request->hasFile($key)) {
                continue;
            }
            $file = $request->file($key);
            $path = $file->storePubliclyAs($dir, "{$key}.".$file->getClientOriginalExtension(), 'public');
            $inspection->setAttribute($key, $path);
        }

        $inspection->save();

        return response()->json([
            'arrival' => $this->arrivalPayload($arrival->load('vendor')),
            'inspection' => $this->inspectionPayload($inspection),
        ]);
    }

    private function arrivalPayload(Arrival $arrival): array
    {
        return [
            'id' => $arrival->id,
            'arrival_no' => $arrival->arrival_no,
            'invoice_no' => $arrival->invoice_no,
            'invoice_date' => optional($arrival->invoice_date)->format('Y-m-d'),
            'vendor' => [
                'id' => $arrival->vendor?->id,
                'name' => $arrival->vendor?->vendor_name,
            ],
            'container_numbers' => $arrival->container_numbers,
            'ETD' => optional($arrival->ETD)->format('Y-m-d'),
            'ETA' => optional($arrival->ETA)->format('Y-m-d'),
        ];
    }

    private function inspectionPayload(ArrivalInspection $inspection): array
    {
        return [
            'id' => $inspection->id,
            'arrival_id' => $inspection->arrival_id,
            'status' => $inspection->status,
            'notes' => $inspection->notes,
            'issues_left' => $inspection->issues_left ?? [],
            'issues_right' => $inspection->issues_right ?? [],
            'issues_front' => $inspection->issues_front ?? [],
            'issues_back' => $inspection->issues_back ?? [],
            'photo_left_url' => $inspection->photo_left ? url(Storage::url($inspection->photo_left)) : null,
            'photo_right_url' => $inspection->photo_right ? url(Storage::url($inspection->photo_right)) : null,
            'photo_front_url' => $inspection->photo_front ? url(Storage::url($inspection->photo_front)) : null,
            'photo_back_url' => $inspection->photo_back ? url(Storage::url($inspection->photo_back)) : null,
            'created_at' => optional($inspection->created_at)->toISOString(),
            'updated_at' => optional($inspection->updated_at)->toISOString(),
        ];
    }
}
