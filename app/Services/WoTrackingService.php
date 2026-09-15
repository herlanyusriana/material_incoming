<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\BomItemSubstitute;
use App\Models\MaterialSubstitute;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Inventory\InventoryStockMovement;
use App\Models\NewSchema\Incoming\IncomingReceive;
use App\Models\NewSchema\Production\ProductionWorkOrder;
use App\Models\NewSchema\Production\WoMaterialAllocation;
use App\Models\NewSchema\Production\WoRequirement;
use App\Support\Uom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * WO Material Tracking — inti "gak boleh lepas dari tracking".
 *
 * 1. Explosion: WO (FG, qty) -> snapshot kebutuhan per komponen (usage_qty × qty).
 * 2. Suggestion: stok per TAG (batch_no) FIFO + minimal split, tag teralokasi
 *    WO lain dikecualikan.
 * 3. Allocate: scan/confirm tag -> RESERVED + guard "WO keluar terkunci".
 * 4. Consume/Return: backflush per tag atau direct_issue saat alokasi.
 * 5. Guard closure: WO tidak bisa CLOSED selama ada alokasi RESERVED.
 */
class WoTrackingService
{
    /**
     * Preview BOM demand and stocked substitute picks without creating a WO.
     *
     * @param  array<int|string, int|string>  $substituteChoices  bom_item_id => substitute_part_id
     */
    public function previewForPart(int $fgPartId, float $qtyTarget, string $startDate, array $substituteChoices = []): array
    {
        $fg = GciPart::query()->findOrFail($fgPartId);
        $wo = new ProductionWorkOrder([
            'gci_part_id' => $fgPartId,
            'qty_target' => $qtyTarget,
            'start_date' => $startDate,
        ]);

        $requirements = collect($this->requirementBlueprints($wo))->map(function (array $blueprint) use ($wo, $substituteChoices) {
            $requirement = new WoRequirement($blueprint);
            $selectedId = isset($substituteChoices[$blueprint['bom_item_id']])
                ? (int) $substituteChoices[$blueprint['bom_item_id']]
                : null;
            $substitutes = $this->stockedSubstituteOptions($requirement);
            if (! $selectedId || ! $substitutes->contains('id', $selectedId)) {
                $selectedId = (int) ($substitutes->first()['id'] ?? 0) ?: null;
            }
            $suggestion = $this->suggestPicks($wo, $requirement, $selectedId);

            return [
                'bom_item_id' => (int) $blueprint['bom_item_id'],
                'generic_part_id' => (int) $blueprint['gci_part_id'],
                'generic_part_no' => $blueprint['component_part_no'],
                'required_qty' => (float) $blueprint['required_qty'],
                'uom' => $blueprint['uom'],
                'substitutes' => $substitutes->values(),
                'selected_substitute_id' => $selectedId,
                'picks' => $suggestion['picks']->values(),
                'shortfall' => (float) $suggestion['shortfall'],
            ];
        })->values();

        return [
            'fg' => [
                'id' => (int) $fg->id,
                'part_no' => $fg->part_no,
                'part_name' => $fg->part_name,
                'model' => $fg->model,
                'uom' => Uom::canonical($fg->uom) ?? 'PCE',
            ],
            'requirements' => $requirements,
            'has_shortfall' => $requirements->contains(fn (array $row) => $row['shortfall'] > 0),
        ];
    }

    // ---------------------------------------------------------------
    // 1. Explosion
    // ---------------------------------------------------------------

    /** Snapshot kebutuhan WO dari BOM aktif part FG. */
    public function buildRequirements(ProductionWorkOrder $wo): void
    {
        $blueprints = $this->requirementBlueprints($wo);
        $wo->requirements()->delete();

        foreach ($blueprints as $blueprint) {
            $wo->requirements()->create($blueprint);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function requirementBlueprints(ProductionWorkOrder $wo): array
    {
        $bom = Bom::activeVersion($wo->gci_part_id, $wo->start_date ?? now());
        if ($wo->exists) {
            $wo->update(['bom_id' => $bom?->id]);
        }
        if (! $bom) {
            return [];
        }

        $items = $bom->items()
            ->with(['componentPart', 'incomingPart.gciPart'])
            ->get();
        $mappedGenericIds = MaterialSubstitute::query()
            ->whereIn('generic_part_id', $items->pluck('component_part_id')->filter())
            ->active()
            ->pluck('generic_part_id')
            ->map(fn ($id) => (int) $id)
            ->flip();
        $mappedBomItemIds = BomItemSubstitute::query()
            ->whereIn('bom_item_id', $items->pluck('id'))
            ->where('status', 'active')
            ->pluck('bom_item_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        return $items
            ->filter(fn (BomItem $item) => $mappedGenericIds->has((int) $item->component_part_id)
                || $mappedBomItemIds->has((int) $item->id))
            ->map(function (BomItem $item) use ($wo) {
                $componentPartId = (int) ($item->incomingPart?->gciPart?->id ?? $item->component_part_id ?? 0);
                $requiredQty = round((float) ($item->net_required ?? $item->usage_qty ?? 0) * (float) $wo->qty_target, 4);
                if ($componentPartId <= 0 || $requiredQty <= 0) {
                    return null;
                }

                return [
                    'gci_part_id' => $componentPartId,
                    'bom_item_id' => (int) $item->id,
                    'component_part_no' => $item->componentPart?->part_no ?? $item->component_part_no,
                    'required_qty' => $requiredQty,
                    'uom' => Uom::canonical($item->componentPart?->uom) ?? Uom::canonical($item->consumption_uom) ?? 'PCE',
                    'consumption_policy' => $this->resolvePolicy($item),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolvePolicy($item): string
    {
        $override = trim((string) ($item->consumption_policy_override ?? ''));
        if ($override !== '') {
            return $override;
        }

        $component = $item->componentPart;
        if ($component && ! empty($component->consumption_policy)) {
            return $component->consumption_policy;
        }

        return ($component?->is_backflush ?? true) ? 'backflush_return' : 'direct_issue';
    }

    // ---------------------------------------------------------------
    // 2. Suggestion engine — pick tag mana, berapa
    // ---------------------------------------------------------------

    /**
     * Saran alokasi untuk satu kebutuhan WO.
     * Aturan: FIFO (receive terlama dulu) + minimal split (coiling utuh dipilih),
     * tag yang sudah dialokasikan ke WO open lain tidak disarankan.
     *
     * @return array{required: float, uom: ?string, picks: Collection, shortfall: float}
     */
    public function suggestPicks(ProductionWorkOrder $wo, WoRequirement $requirement, ?int $substitutePartId = null): array
    {
        $alreadyForThisWo = $wo->exists && $requirement->exists
            ? (float) WoMaterialAllocation::query()
                ->where('work_order_id', $wo->id)
                ->where('requirement_id', $requirement->id)
                ->whereIn('status', [WoMaterialAllocation::STATUS_RESERVED, WoMaterialAllocation::STATUS_CONSUMED])
                ->sum('qty_reserved')
                - (float) WoMaterialAllocation::query()
                    ->where('work_order_id', $wo->id)
                    ->where('requirement_id', $requirement->id)
                    ->where('status', WoMaterialAllocation::STATUS_RETURNED)
                    ->sum('qty_returned')
            : 0;

        $remaining = max(0, (float) $requirement->required_qty - $alreadyForThisWo);

        $substituteIds = $this->eligibleSubstituteIds($requirement);
        if ($substitutePartId !== null) {
            $substituteIds = $substituteIds->filter(fn ($id) => (int) $id === $substitutePartId)->values();
        }

        // WO ini sengaja hanya memakai substitute. Main component tidak pernah
        // menjadi kandidat stock/pick.
        $tagStock = InventoryLocationStock::query()
            ->whereIn('gci_part_id', $substituteIds)
            ->where('qty_on_hand', '>', 0)
            ->whereRaw("TRIM(COALESCE(batch_no,'')) <> ''")
            ->get(['id', 'gci_part_id', 'location_code', 'batch_no', 'qty_on_hand']);

        $parts = GciPart::query()->whereIn('id', $tagStock->pluck('gci_part_id'))->get()->keyBy('id');
        $receives = IncomingReceive::query()
            ->whereIn('tag', $tagStock->pluck('batch_no'))
            ->with(['arrivalItem.arrival.vendor'])
            ->orderBy('id')
            ->get()
            ->unique('tag')
            ->keyBy('tag');

        $tags = $tagStock->map(function ($row) use ($parts, $receives) {
            $part = $parts->get((int) $row->gci_part_id);
            $receive = $receives->get($row->batch_no);

            // Stok coil masuk sebagai net_weight; tag non-coil qty langsung.
            return [
                'tag' => $row->batch_no,
                'location_code' => $row->location_code,
                'qty_on_hand' => (float) $row->qty_on_hand,
                'receive_id' => $receive?->id,
                'age' => $receive?->ata_date ?? $receive?->created_at,
                'gci_part_id' => (int) $row->gci_part_id,
                'part_no' => $part?->part_no,
                'part_name' => $part?->part_name,
                'model' => $part?->model,
                'size' => $part?->size,
                'invoice_no' => $receive?->arrivalItem?->arrival?->invoice_no,
                'supplier' => $receive?->arrivalItem?->arrival?->vendor?->vendor_name,
                'received_at' => $receive?->ata_date?->toDateString() ?? $receive?->created_at?->toDateString(),
                'reserved_elsewhere' => false, // diisi di bawah
            ];
        })
            // Tag teralokasi ke WO open lain → tidak boleh disarankan (dobel alokasi).
            ->reject(function ($t) use ($wo) {
                $openWoIds = ProductionWorkOrder::query()
                    ->whereNot('id', $wo->id)
                    ->whereIn('status', ['PLANNED', 'RESERVED', 'RELEASED', 'IN_PRODUCTION'])
                    ->pluck('id');

                return $openWoIds->isNotEmpty() && WoMaterialAllocation::query()
                    ->whereIn('work_order_id', $openWoIds)
                    ->where('status', WoMaterialAllocation::STATUS_RESERVED)
                    ->where('tag', $t['tag'])
                    ->where('gci_part_id', '!=', 0)
                    ->exists();
            })
            // FIFO
            ->sortBy(fn ($t) => (string) ($t['age'] ?? '9999-12-31'))
            ->values();

        // Greedy: ambil tag utuh dulu, split hanya di tag terakhir.
        $need = $remaining;
        $picks = collect();
        foreach ($tags as $t) {
            if ($need <= 0) {
                break;
            }
            $take = min($need, $t['qty_on_hand']);
            $picks->push([
                'tag' => $t['tag'],
                'gci_part_id' => $t['gci_part_id'],
                'part_no' => $t['part_no'],
                'part_name' => $t['part_name'],
                'model' => $t['model'],
                'size' => $t['size'],
                'invoice_no' => $t['invoice_no'],
                'supplier' => $t['supplier'],
                'received_at' => $t['received_at'],
                'location_code' => $t['location_code'],
                'qty' => round($take, 4),
                'qty_on_hand' => $t['qty_on_hand'],
                'receive_id' => $t['receive_id'],
                'split' => $take < $t['qty_on_hand'],
            ]);
            $need -= $take;
        }

        return [
            'required' => (float) $requirement->required_qty,
            'uom' => $requirement->uom,
            'picks' => $picks,
            'shortfall' => round(max(0, $need), 4),
        ];
    }

    public function isEligibleSubstitute(WoRequirement $requirement, int $partId): bool
    {
        return $this->eligibleSubstituteIds($requirement)->contains($partId);
    }

    private function eligibleSubstituteIds(WoRequirement $requirement): Collection
    {
        $global = MaterialSubstitute::query()
            ->where('generic_part_id', $requirement->gci_part_id)
            ->active()
            ->pluck('substitute_part_id');
        $legacy = $requirement->bom_item_id
            ? BomItemSubstitute::query()
                ->where('bom_item_id', $requirement->bom_item_id)
                ->where('status', 'active')
                ->pluck('substitute_part_id')
            : collect();

        return $global->merge($legacy)->filter()->map(fn ($id) => (int) $id)->unique()->values();
    }

    private function stockedSubstituteOptions(WoRequirement $requirement): Collection
    {
        $ids = $this->eligibleSubstituteIds($requirement);
        if ($ids->isEmpty()) {
            return collect();
        }

        $reservedTags = WoMaterialAllocation::query()
            ->whereIn('work_order_id', ProductionWorkOrder::query()
                ->whereIn('status', ['PLANNED', 'RESERVED', 'RELEASED', 'IN_PRODUCTION'])
                ->select('id'))
            ->where('status', WoMaterialAllocation::STATUS_RESERVED)
            ->pluck('tag')
            ->filter()
            ->unique();
        $stock = InventoryLocationStock::query()
            ->whereIn('gci_part_id', $ids)
            ->where('qty_on_hand', '>', 0)
            ->when($reservedTags->isNotEmpty(), fn ($query) => $query->whereNotIn('batch_no', $reservedTags))
            ->selectRaw('gci_part_id, SUM(qty_on_hand) as available_qty')
            ->groupBy('gci_part_id')
            ->pluck('available_qty', 'gci_part_id');
        $mappings = MaterialSubstitute::query()
            ->where('generic_part_id', $requirement->gci_part_id)
            ->whereIn('substitute_part_id', $stock->keys())
            ->with('vendorPart.vendor')
            ->get()
            ->keyBy('substitute_part_id');

        return GciPart::query()
            ->whereIn('id', $stock->keys())
            ->get(['id', 'part_no', 'part_name', 'model', 'size', 'uom'])
            ->map(function (GciPart $part) use ($stock, $mappings) {
                $mapping = $mappings->get($part->id);

                return [
                    'id' => (int) $part->id,
                    'part_no' => $part->part_no,
                    'part_name' => $part->part_name,
                    'model' => $part->model,
                    'size' => $part->size,
                    'uom' => Uom::canonical($part->uom) ?? 'PCE',
                    'available_qty' => (float) $stock->get($part->id),
                    'supplier' => $mapping?->vendorPart?->vendor?->vendor_name,
                ];
            })
            ->sortBy(fn (array $part) => $part['part_no'])
            ->values();
    }

    /** Cek tag dari hasil scan: milik WO mana, masih boleh dialokasi? */
    public function locateTag(string $tag): array
    {
        $receive = IncomingReceive::query()
            ->where('tag', $tag)
            ->whereNull('deleted_at')
            ->with(['arrivalItem.gciPart'])
            ->first();

        $stockRows = InventoryLocationStock::query()
            ->where('batch_no', $tag)
            ->where('qty_on_hand', '>', 0)
            ->get(['gci_part_id', 'location_code', 'qty_on_hand']);

        $stockPart = $stockRows->first()?->gci_part_id
            ? GciPart::find((int) $stockRows->first()->gci_part_id)
            : null;

        $activeWoIds = ProductionWorkOrder::query()
            ->whereIn('status', ['PLANNED', 'RESERVED', 'RELEASED', 'IN_PRODUCTION'])
            ->pluck('id');

        $reservations = WoMaterialAllocation::query()
            ->whereIn('work_order_id', $activeWoIds)
            ->where('status', WoMaterialAllocation::STATUS_RESERVED)
            ->where('tag', $tag)
            ->with('workOrder')
            ->get(['work_order_id', 'qty_reserved', 'qty_consumed']);

        return [
            'found' => $receive !== null,
            'tag' => $tag,
            'gci_part_id' => (int) ($stockPart?->id ?? $receive?->arrivalItem?->gci_part_id ?? 0),
            'part_no' => $stockPart?->part_no ?? $receive?->arrivalItem?->gciPart?->part_no,
            'part_name' => $stockPart?->part_name ?? $receive?->arrivalItem?->gciPart?->part_name,
            'uom' => Uom::canonical($stockPart?->uom ?? $receive?->arrivalItem?->gciPart?->uom),
            'locations' => $stockRows->map(fn ($r) => [
                'location_code' => $r->location_code,
                'qty_on_hand' => (float) $r->qty_on_hand,
            ])->values()->all(),
            'reserved_by' => $reservations->map(fn ($r) => [
                'work_order_id' => (int) $r->work_order_id,
                'work_order_no' => $r->workOrder?->work_order_no,
                'qty_reserved' => (float) $r->qty_reserved,
            ])->values()->all(),
        ];
    }

    // ---------------------------------------------------------------
    // 3. Allocate — kunci tag ke WO
    // ---------------------------------------------------------------

    /**
     * Kunci tag ke WO. Direct_issue: langsung CONSUMED (stok berkurang saat itu).
     * Backflush: RESERVED, konsumsi menunggu input hasil.
     */
    public function allocate(ProductionWorkOrder $wo, WoRequirement $requirement, string $tag, float $qty, ?string $locationCode = null): WoMaterialAllocation
    {
        return DB::transaction(function () use ($wo, $requirement, $tag, $qty, $locationCode) {
            $wo = ProductionWorkOrder::query()->whereKey($wo->id)->lockForUpdate()->firstOrFail();

            if (in_array($wo->status, ['CLOSED', 'CANCELLED'], true)) {
                abort(422, 'WO sudah ditutup/dibatalkan — material tidak bisa dialokasi.');
            }

            $qty = round($qty, 4);
            if ($qty <= 0) {
                abort(422, 'Qty alokasi harus > 0.');
            }

            // Stok nyata di tag+lokasi.
            $location = strtoupper(trim((string) ($locationCode ?? '')));
            $stockRow = InventoryLocationStock::query()
                ->where('batch_no', $tag)
                ->where('location_code', $location)
                ->where('qty_on_hand', '>', 0)
                ->first(['gci_part_id', 'qty_on_hand']);
            $substituteIds = $this->eligibleSubstituteIds($requirement);
            if (! $stockRow || ! $substituteIds->contains((int) $stockRow->gci_part_id)) {
                abort(422, "Tag {$tag} bukan substitute aktif untuk kebutuhan WO ini.");
            }
            $actualPartId = (int) $stockRow->gci_part_id;
            $available = (float) $stockRow->qty_on_hand;
            if ($available + 1e-9 < $qty) {
                abort(422, "Stok tag {$tag} di {$location} tidak cukup: butuh {$qty}, tersedia {$available}.");
            }

            // Dobel alokasi lintas WO.
            $reserved = (float) WoMaterialAllocation::query()
                ->whereIn('work_order_id', ProductionWorkOrder::query()
                    ->whereNot('id', $wo->id)
                    ->whereIn('status', ['PLANNED', 'RESERVED', 'RELEASED', 'IN_PRODUCTION'])
                    ->select('id'))
                ->where('gci_part_id', $actualPartId)
                ->where('tag', $tag)
                ->where('status', WoMaterialAllocation::STATUS_RESERVED)
                ->sum('qty_reserved');
            if ($available - $reserved + 1e-9 < $qty) {
                abort(422, "Tag {$tag} sudah dialokasikan {$reserved} ke WO lain. Sisa bebas: {$available}.");
            }

            $allocation = WoMaterialAllocation::create([
                'work_order_id' => $wo->id,
                'requirement_id' => $requirement->id,
                'gci_part_id' => $actualPartId,
                'tag' => $tag,
                'location_code' => $location,
                'qty_reserved' => $qty,
                'status' => WoMaterialAllocation::STATUS_RESERVED,
                'receive_id' => IncomingReceive::query()->where('tag', $tag)->value('id'),
                'allocated_by' => auth()->id(),
                'allocated_at' => now(),
            ]);

            if ($requirement->consumption_policy === 'direct_issue') {
                $this->consumeAllocation($allocation, $qty);
            }

            $this->refreshWoStatus($wo);

            return $allocation->fresh();
        });
    }

    // ---------------------------------------------------------------
    // 4. Consume / Return — backflush per tag
    // ---------------------------------------------------------------

    /**
     * Konsumsi qty dari alokasi (FIFO antar alokasi RESERVED bila qty
     * melebihi satu alokasi). Stok dikurangi dari lokasi+tag alokasi,
     * movement membawa ref WO.
     */
    public function consumeForWo(ProductionWorkOrder $wo, WoRequirement $requirement, float $qty): void
    {
        $remaining = round($qty, 4);
        if ($remaining <= 0) {
            return;
        }

        $allocations = WoMaterialAllocation::query()
            ->where('work_order_id', $wo->id)
            ->where('requirement_id', $requirement->id)
            ->where('status', WoMaterialAllocation::STATUS_RESERVED)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($allocations as $allocation) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, $allocation->openQty());
            if ($take <= 0) {
                continue;
            }
            $this->consumeAllocation($allocation, $take);
            $remaining -= $take;
        }
    }

    public function consumeAllocation(WoMaterialAllocation $allocation, float $qty): void
    {
        DB::transaction(function () use ($allocation, $qty) {
            $allocation = WoMaterialAllocation::query()->whereKey($allocation->id)->lockForUpdate()->firstOrFail();
            $take = min(round($qty, 4), $allocation->openQty());
            if ($take <= 0) {
                return;
            }

            $wo = $allocation->workOrder;

            InventoryLocationStock::updateStock(
                (int) $allocation->gci_part_id,
                (string) $allocation->location_code,
                -$take,
                $allocation->tag,
                $allocation->tag,
                'WO_CONSUME',
                "WO#{$allocation->work_order_id} " . ($wo?->work_order_no ?? ''),
                null,
                null,
                null,
                null,
                null,
                auth()->id()
            );

            $allocation->qty_consumed = (float) $allocation->qty_consumed + $take;
            $allocation->consumed_at = now();
            $allocation->consumed_by = auth()->id();
            $allocation->status = $allocation->openQty() <= 1e-9
                ? WoMaterialAllocation::STATUS_CONSUMED
                : WoMaterialAllocation::STATUS_RESERVED;
            $allocation->movement_ref = "WO#{$allocation->work_order_id}";
            $allocation->save();
        });
    }

    /** Kembalikan sisa alokasi ke rak (movement RETURN, ref WO). */
    public function returnAllocation(WoMaterialAllocation $allocation, ?float $qty = null): void
    {
        DB::transaction(function () use ($allocation, $qty) {
            $allocation = WoMaterialAllocation::query()->whereKey($allocation->id)->lockForUpdate()->firstOrFail();
            $take = min(round($qty ?? $allocation->openQty(), 4), $allocation->openQty());
            if ($take <= 0) {
                return;
            }

            $wo = $allocation->workOrder;

            InventoryLocationStock::updateStock(
                (int) $allocation->gci_part_id,
                (string) $allocation->location_code,
                $take,
                $allocation->tag,
                $allocation->tag,
                'WO_RETURN',
                "WO#{$allocation->work_order_id} " . ($wo?->work_order_no ?? ''),
                null,
                null,
                null,
                null,
                null,
                auth()->id()
            );

            $allocation->qty_returned = (float) $allocation->qty_returned + $take;
            $allocation->returned_at = now();
            $allocation->status = WoMaterialAllocation::STATUS_RETURNED;
            $allocation->save();
        });
    }

    /** Batalkan WO: semua alokasi RESERVED dikembalikan. */
    public function cancelWo(ProductionWorkOrder $wo): void
    {
        DB::transaction(function () use ($wo) {
            $wo = ProductionWorkOrder::query()->whereKey($wo->id)->lockForUpdate()->firstOrFail();

            if (in_array($wo->status, ['CLOSED', 'CANCELLED'], true)) {
                return;
            }

            foreach ($wo->allocations()->where('status', WoMaterialAllocation::STATUS_RESERVED)->get() as $allocation) {
                $this->returnAllocation($allocation);
            }

            $wo->update(['status' => 'CANCELLED']);
        });
    }

    // ---------------------------------------------------------------
    // 5. Status + guard closure
    // ---------------------------------------------------------------

    public function refreshWoStatus(ProductionWorkOrder $wo): void
    {
        $wo->refresh();

        if (in_array($wo->status, ['CLOSED', 'CANCELLED'], true)) {
            return;
        }

        $fullyReserved = $wo->requirements->every(function (WoRequirement $requirement) use ($wo) {
            $directPolicy = $requirement->consumption_policy === 'direct_issue';
            $covered = (float) $wo->allocations()
                ->where('requirement_id', $requirement->id)
                ->whereIn('status', [WoMaterialAllocation::STATUS_RESERVED, WoMaterialAllocation::STATUS_CONSUMED])
                ->sum('qty_reserved');

            return $covered + 1e-9 >= (float) $requirement->required_qty
                || ($directPolicy && $covered >= 0 && $requirement->required_qty <= 0);
        });

        if ($wo->requirements->isNotEmpty() && $fullyReserved && ! in_array($wo->status, ['RELEASED', 'IN_PRODUCTION'], true)) {
            $wo->update(['status' => 'RELEASED']);
        }
    }

    /**
     * Posting hasil produksi: terapkan consumption Policy, input FG ke gudang
     * (lot = WO no), lalu tentukan status WO.
     *
     * @return array{consumed: float, fg_location: ?string, closed: bool}
     */
    public function applyProductionResult(ProductionWorkOrder $wo, float $qtyGood, float $qtyNg = 0): array
    {
        $qtyGood = round(max(0, $qtyGood), 4);
        if ($qtyGood <= 0) {
            abort(422, 'Qty hasil baik harus > 0.');
        }

        if (in_array($wo->status, ['CLOSED', 'CANCELLED'], true)) {
            abort(422, 'WO sudah ditutup — tidak bisa posting hasil.');
        }

        $target = max((float) $wo->qty_target, 0.0001);
        $ratio = min(1, $qtyGood / $target);
        // Kunci requirement: dua posting paralel tidak boleh konsumsi dobel.
        WoRequirement::query()->where('work_order_id', $wo->id)->lockForUpdate()->pluck('id');
        $wo->load('requirements');

        $consumed = 0.0;
        foreach ($wo->requirements as $requirement) {
            // direct_issue sudah dikonsumsi saat alokasi — jangan dobel.
            if ($requirement->consumption_policy === 'direct_issue') {
                continue;
            }
            $plan = round((float) $requirement->required_qty * $ratio, 4);
            if ($plan <= 0) {
                continue;
            }
            $before = (float) $wo->allocations()->where('requirement_id', $requirement->id)->sum('qty_consumed');
            $this->consumeForWo($wo, $requirement, $plan);
            $consumed += (float) $wo->allocations()->where('requirement_id', $requirement->id)->sum('qty_consumed') - $before;
        }

        $fg = $wo->gciPart;
        $fgLocation = null;
        if ($fg && trim((string) $fg->default_location) !== '') {
            $fgLocation = strtoupper(trim((string) $fg->default_location));
            InventoryLocationStock::updateStock(
                (int) $fg->id,
                $fgLocation,
                $qtyGood,
                (string) $wo->work_order_no,
                (string) $wo->work_order_no,
                'WO_FG_OUTPUT',
                "WO#{$wo->id} {$wo->work_order_no}",
                null,
                null,
                null,
                null,
                null,
                auth()->id()
            );
        }

        $wo->qty_actual = round(min((float) $wo->qty_target, (float) $wo->qty_actual + $qtyGood), 4);
        $wo->updated_by = auth()->id();
        // Target tercapai BELUM cukup — sisa coil RESERVED harus diselesaikan
        // (return/deallocate) dulu lewat guard, baru WO boleh CLOSED.
        $closed = (float) $wo->qty_actual + 1e-9 >= (float) $wo->qty_target && ! $wo->hasOpenAllocations();
        $wo->status = $closed ? 'CLOSED' : 'IN_PRODUCTION';
        if ($closed) {
            $wo->end_date = now()->toDateString();
        }
        $wo->save();

        return [
            'consumed' => round($consumed, 4),
            'fg_location' => $fgLocation,
            'closed' => $closed,
            'qty_ng' => round(max(0, $qtyNg), 4),
        ];
    }

    /**
     * Tutup manual: sisanya wajib terselesaikan dulu (guard assertClosable).
     */
    public function closeWo(ProductionWorkOrder $wo): void
    {
        if (in_array($wo->status, ['CLOSED', 'CANCELLED'], true)) {
            abort(422, 'WO sudah ditutup.');
        }
        $this->assertClosable($wo);

        $wo->update([
            'status' => 'CLOSED',
            'end_date' => now()->toDateString(),
            'updated_by' => auth()->id(),
        ]);
    }

    /** Guard: WO tidak boleh closed selama ada alokasi RESERVED. */
    public function assertClosable(ProductionWorkOrder $wo): void
    {
        $open = $wo->allocations()
            ->where('status', WoMaterialAllocation::STATUS_RESERVED)
            ->with('gciPart')
            ->get();

        if ($open->isNotEmpty()) {
            $detail = $open->map(fn ($a) => "{$a->tag} (" . rtrim(rtrim((string) $a->openQty(), '0'), '.') . ')')->implode(', ');
            abort(422, "WO belum bisa ditutup — masih ada material teralokasi: {$detail}. Selesaikan produksi (konsumsi) atau kembalikan ke gudang.");
        }
    }
}
