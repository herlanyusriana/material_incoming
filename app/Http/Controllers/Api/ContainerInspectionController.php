<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arrival;
use App\Models\ArrivalContainer;
use App\Models\ArrivalContainerInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ContainerInspectionController extends Controller
{
    public function listByArrival(Arrival $arrival)
    {
        $arrival->load(['vendor', 'containers.inspection']);

        return response()->json([
            'arrival' => $this->arrivalPayload($arrival),
        ]);
    }

    public function show(ArrivalContainer $container)
    {
        $container->load(['arrival.vendor', 'inspection']);

        return response()->json([
            'arrival' => $this->arrivalPayload($container->arrival->load(['containers.inspection'])),
            'container' => $this->containerPayload($container),
            'inspection' => $container->inspection ? $this->inspectionPayload($container->inspection) : null,
        ]);
    }

    public function upsert(Request $request, ArrivalContainer $container)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['ok', 'damage'])],
            'seal_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'driver_name' => ['nullable', 'string', 'max:150'],
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
            'photo_inside' => ['nullable', 'image', 'max:10240'],
            'photo_seal' => ['nullable', 'image', 'max:10240'],
            'photo_damage' => ['nullable', 'image', 'max:10240'],
        ]);

        $sealCode = array_key_exists('seal_code', $validated)
            ? (is_string($validated['seal_code']) ? trim($validated['seal_code']) : null)
            : null;

        if ($sealCode !== null && $sealCode !== '') {
            $container->seal_code = $sealCode;
            $container->save();
        }

        $inspection = ArrivalContainerInspection::firstOrNew(['arrival_container_id' => $container->id]);
        $inspection->fill([
            'status' => $validated['status'],
            'seal_code' => $sealCode ?: null,
            'notes' => $validated['notes'] ?? null,
            'driver_name' => isset($validated['driver_name']) && trim((string) $validated['driver_name']) !== '' ? trim((string) $validated['driver_name']) : null,
            'issues_left' => $validated['issues_left'] ?? [],
            'issues_right' => $validated['issues_right'] ?? [],
            'issues_front' => $validated['issues_front'] ?? [],
            'issues_back' => $validated['issues_back'] ?? [],
            'inspected_by' => $request->user()?->id,
        ]);

        $dir = "inspections/arrival-{$container->arrival_id}/container-{$container->id}";
        foreach (['left', 'right', 'front', 'back', 'inside', 'seal', 'damage'] as $side) {
            $key = "photo_{$side}";
            if (!$request->hasFile($key)) {
                continue;
            }
            $file = $request->file($key);
            $path = $file->storePubliclyAs($dir, "{$key}." . $file->getClientOriginalExtension(), 'public');
            $inspection->setAttribute($key, $path);
        }

        $inspection->save();

        $container->load(['arrival.vendor', 'inspection']);

        return response()->json([
            'arrival' => $this->arrivalPayload($container->arrival->load(['containers.inspection'])),
            'container' => $this->containerPayload($container),
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
            'containers' => $arrival->containers
                ? $arrival->containers->map(fn ($c) => $this->containerPayload($c))->values()
                : [],
        ];
    }

    private function containerPayload(ArrivalContainer $container): array
    {
        return [
            'id' => $container->id,
            'arrival_id' => $container->arrival_id,
            'container_no' => $container->container_no,
            'seal_code' => $container->seal_code,
            'inspected' => (bool) $container->inspection,
        ];
    }

    private function inspectionPayload(ArrivalContainerInspection $inspection): array
    {
        return [
            'id' => $inspection->id,
            'arrival_container_id' => $inspection->arrival_container_id,
            'status' => $inspection->status,
            'seal_code' => $inspection->seal_code,
            'notes' => $inspection->notes,
            'driver_name' => $inspection->driver_name,
            'issues_left' => $inspection->issues_left ?? [],
            'issues_right' => $inspection->issues_right ?? [],
            'issues_front' => $inspection->issues_front ?? [],
            'issues_back' => $inspection->issues_back ?? [],
            'photo_left_url' => $inspection->photo_left ? url(Storage::url($inspection->photo_left)) : null,
            'photo_right_url' => $inspection->photo_right ? url(Storage::url($inspection->photo_right)) : null,
            'photo_front_url' => $inspection->photo_front ? url(Storage::url($inspection->photo_front)) : null,
            'photo_back_url' => $inspection->photo_back ? url(Storage::url($inspection->photo_back)) : null,
            'photo_inside_url' => $inspection->photo_inside ? url(Storage::url($inspection->photo_inside)) : null,
            'photo_seal_url' => $inspection->photo_seal ? url(Storage::url($inspection->photo_seal)) : null,
            'photo_damage_url' => $inspection->photo_damage ? url(Storage::url($inspection->photo_damage)) : null,
            'created_at' => optional($inspection->created_at)->toISOString(),
            'updated_at' => optional($inspection->updated_at)->toISOString(),
        ];
    }
}
