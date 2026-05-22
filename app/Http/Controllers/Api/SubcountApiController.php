<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubcountBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubcountApiController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => ['nullable', 'string', 'max:100'],
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
            $batch = SubcountBatch::updateOrCreate(
                ['subcount_no' => $validated['subcount_no']],
                [
                    'external_id' => $validated['id'] ?? null,
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
