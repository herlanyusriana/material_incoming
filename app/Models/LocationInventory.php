<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LocationInventory extends Model
{
    use HasFactory;

    protected $table = 'location_inventory';

    protected $fillable = [
        'gci_part_id', // The Master ID (Required)
        'part_id',      // The Vendor Part ID (Nullable, for RM)
        'location_code',
        'batch_no',
        'production_date',
        'qty_on_hand',
        'last_counted_at',
    ];

    protected $casts = [
        'qty_on_hand' => 'decimal:4',
        'production_date' => 'date',
        'last_counted_at' => 'datetime',
    ];

    /**
     * Get the Internal Master Part
     */
    public function gciPart()
    {
        return $this->belongsTo(GciPart::class);
    }

    /**
     * Get the vendor part (if applicable)
     */
    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * Get the warehouse location
     */
    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_code', 'location_code');
    }

    /**
     * Get stock quantity for a specific part at a specific location
     */
    public static function getStockByLocation(int $partId, string $locationCode, ?string $batchNo = null): float
    {
        $q = self::query()
            ->where('part_id', $partId)
            ->where('location_code', strtoupper(trim($locationCode)));

        $batchNo = $batchNo !== null ? strtoupper(trim($batchNo)) : null;
        if ($batchNo !== null && $batchNo !== '') {
            $q->where('batch_no', $batchNo);
            $record = $q->first();
            return $record ? (float) $record->qty_on_hand : 0;
        }

        return (float) $q->sum('qty_on_hand');
    }

    /**
     * Update stock for a part at a location
     * Positive qtyChange = increase, Negative = decrease
     */
    /**
     * Update stock for a part at a location
     * Positive qtyChange = increase, Negative = decrease
     * For FG/WIP (Internal only), pass $partId as null and provide $gciPartId.
     */
    public static function updateStock(?int $partId, string $locationCode, float $qtyChange, ?string $batchNo = null, ?string $productionDate = null, ?int $gciPartId = null): void
    {
        $locationCode = strtoupper(trim($locationCode));
        $batchNo = $batchNo !== null ? strtoupper(trim($batchNo)) : null;
        if ($batchNo === '') {
            $batchNo = null;
        }

        // Auto-resolve GCI Part ID from Vendor Part if not provided
        if ($partId && !$gciPartId) {
            $part = Part::find($partId);
            if ($part) {
                $gciPartId = $part->gci_part_id;
            }
        }

        if (!$gciPartId && !$partId) {
            throw new \Exception("Must provide either Vendor Part ID or GCI Part ID for inventory update.");
        }

        // Search attributes
        $attributes = [
            'location_code' => $locationCode,
            'batch_no' => $batchNo,
        ];

        // If Vendor Part is known, use it for unicity. If FG, use GCI.
        // Actually, we should match by what is provided.
        if ($partId) {
            $attributes['part_id'] = $partId;
        } else {
            $attributes['gci_part_id'] = $gciPartId;
            $attributes['part_id'] = null;
        }

        $record = self::firstOrCreate(
            $attributes,
            [
                'qty_on_hand' => 0,
                'production_date' => $productionDate ?: null,
                'gci_part_id' => $gciPartId, // Ensure GCI ID is set on creation
                'part_id' => $partId,
            ]
        );

        // Ensure GCI ID is populated if it was missing 
        if (!$record->gci_part_id && $gciPartId) {
            $record->gci_part_id = $gciPartId;
            $record->save();
        }

        $newQty = (float) $record->qty_on_hand + $qtyChange;

        if ($newQty < 0) {
            throw new \Exception("Cannot reduce stock below zero. Current: {$record->qty_on_hand}, Change: {$qtyChange}");
        }

        $record->update(['qty_on_hand' => $newQty]);
    }

    /**
     * Consume stock (decrement) with optional batch allocation.
     * Unified Logic: 
     * - If $gciPartId is provided (e.g. from BOM), consumes ANY stock linked to that Master Part.
     * - If $partId is provided, resolves its Master Part first (if any) and consumes Master stock.
     */
    public static function consumeStock(?int $partId, string $locationCode, float $qty, ?string $batchNo = null, ?int $gciPartId = null): void
    {
        $qty = (float) $qty;
        if ($qty <= 0) {
            return;
        }

        $locationCode = strtoupper(trim($locationCode));
        $batchNo = $batchNo !== null ? strtoupper(trim($batchNo)) : null;
        if ($batchNo === '') {
            $batchNo = null;
        }

        // --- Unified Logic Start ---
        // Determine the scope of consumption.
        // If we are given a Vendor Part ID, we should check if it's linked to a Master.
        // Ideally, we want to consume ANY stock of that Master Part.

        $gciPartId = null;
        // Check if $partId is actually a GCI Part ID? (If we were passing GCI IDs directly)
        // But current signature expects Vendor Part ID usually.
        // Let's assume standard behavior first: 
        $part = Part::find($partId);
        if ($part && $part->gci_part_id) {
            $gciPartId = $part->gci_part_id;
        }

        if ($batchNo !== null) {
            // Specific batch - likely specific vendor part too if batch is unique? 
            // Or just update specific record.
            self::updateStock($partId, $locationCode, -$qty, $batchNo);
            return;
        }

        $remaining = $qty;

        $query = self::query()
            ->where('location_code', $locationCode)
            ->where('qty_on_hand', '>', 0);

        if ($gciPartId) {
            // Unified Consumption: Look for ANY stock matching the Master Part
            $query->where('gci_part_id', $gciPartId);
        } else {
            // Legacy / Unlinked: Look for specific Vendor Part
            $query->where('part_id', $partId);
        }

        $rows = $query
            ->orderByRaw('production_date IS NULL') // non-null first
            ->orderBy('production_date')
            ->orderBy('batch_no')
            ->lockForUpdate()
            ->get();
        // --- Unified Logic End ---

        foreach ($rows as $row) {
            /** @var LocationInventory $row */
            if ($remaining <= 0) {
                break;
            }

            $available = (float) $row->qty_on_hand;
            if ($available <= 0) {
                continue;
            }

            $take = min($available, $remaining);
            $row->update(['qty_on_hand' => $available - $take]);
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \Exception("Not enough stock at {$locationCode}. Need {$qty}, remaining {$remaining}.");
        }
    }

    /**
     * Get all locations for a specific part with stock
     */
    public static function getLocationsForPart(int $partId)
    {
        return self::query()
            ->where('part_id', $partId)
            ->where('qty_on_hand', '>', 0)
            ->with('location')
            ->orderBy('location_code')
            ->orderBy('batch_no')
            ->get();
    }

    /**
     * Get stock summary for a part across all locations
     */
    public static function getStockSummary(int $partId): array
    {
        $locations = self::where('part_id', $partId)
            ->where('qty_on_hand', '>', 0)
            ->get();

        return [
            'total_qty' => $locations->sum('qty_on_hand'),
            'location_count' => $locations->count(),
            'locations' => $locations->map(function ($loc) {
                return [
                    'location_code' => $loc->location_code,
                    'qty' => $loc->qty_on_hand,
                ];
            })->toArray(),
        ];
    }
}
