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
     * Boot method to register model events
     */
    protected static function booted(): void
    {
        // After any stock change, sync summary tables
        static::saved(function (LocationInventory $locationInventory) {
            $locationInventory->syncSummaryTables();
        });

        static::deleted(function (LocationInventory $locationInventory) {
            $locationInventory->syncSummaryTables();
        });
    }

    /**
     * Sync summary tables (inventories & gci_inventories) after stock change
     */
    protected function syncSummaryTables(): void
    {
        // Sync Inventory (vendor parts summary)
        if ($this->part_id) {
            $this->syncInventorySummary($this->part_id);
        }

        // Sync GciInventory (internal parts summary)
        if ($this->gci_part_id) {
            $this->syncGciInventorySummary($this->gci_part_id);
        }
    }

    /**
     * Sync Inventory table for vendor parts
     */
    protected function syncInventorySummary(int $partId): void
    {
        $totalOnHand = self::where('part_id', $partId)->sum('qty_on_hand');

        Inventory::updateOrCreate(
            ['part_id' => $partId],
            [
                'on_hand' => $totalOnHand,
                'as_of_date' => now(),
            ]
        );
    }

    /**
     * Sync GciInventory table for internal parts
     */
    protected function syncGciInventorySummary(int $gciPartId): void
    {
        $totalOnHand = self::where('gci_part_id', $gciPartId)->sum('qty_on_hand');

        GciInventory::updateOrCreate(
            ['gci_part_id' => $gciPartId],
            [
                'on_hand' => $totalOnHand,
                'as_of_date' => now(),
            ]
        );
    }

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
     * Get stock quantity for a specific part at a specific location.
     * Bisa pakai part_id (vendor) atau gci_part_id (master).
     */
    public static function getStockByLocation(int $partId, string $locationCode, ?string $batchNo = null, ?int $gciPartId = null): float
    {
        // Resolve ke gci_part_id kalau bisa
        if (!$gciPartId) {
            $part = Part::find($partId);
            $gciPartId = $part?->gci_part_id;
        }

        $q = self::query()
            ->where('location_code', strtoupper(trim($locationCode)));

        // Prefer gci_part_id untuk query (semua stock master part)
        if ($gciPartId) {
            $q->where('gci_part_id', $gciPartId);
        } else {
            $q->where('part_id', $partId);
        }

        $batchNo = $batchNo !== null ? strtoupper(trim($batchNo)) : null;
        if ($batchNo !== null && $batchNo !== '') {
            $q->where('batch_no', $batchNo);
            $record = $q->first();
            return $record ? (float) $record->qty_on_hand : 0;
        }

        return (float) $q->sum('qty_on_hand');
    }

    /**
     * Update stock for a part at a location.
     * Positive qtyChange = increase, Negative = decrease.
     *
     * gci_part_id is the PRIMARY reference (required).
     * part_id (vendor part) is optional — hanya untuk tracking vendor.
     */
    public static function updateStock(?int $partId, string $locationCode, float $qtyChange, ?string $batchNo = null, ?string $productionDate = null, ?int $gciPartId = null): void
    {
        $locationCode = strtoupper(trim($locationCode));
        $batchNo = $batchNo !== null ? strtoupper(trim($batchNo)) : null;
        if ($batchNo === '') {
            $batchNo = null;
        }

        // Auto-resolve gci_part_id dari part_id (vendor part) jika belum ada
        if ($partId && !$gciPartId) {
            $part = Part::find($partId);
            if ($part && $part->gci_part_id) {
                $gciPartId = $part->gci_part_id;
            }
        }

        // gci_part_id WAJIB ada — ini primary reference
        if (!$gciPartId) {
            throw new \Exception("gci_part_id wajib ada untuk update inventory. part_id={$partId}");
        }

        // Search attributes — pakai gci_part_id sebagai primary key
        $attributes = [
            'gci_part_id' => $gciPartId,
            'location_code' => $locationCode,
            'batch_no' => $batchNo,
        ];

        $record = self::firstOrCreate(
            $attributes,
            [
                'qty_on_hand' => 0,
                'production_date' => $productionDate ?: null,
                'part_id' => $partId,
            ]
        );

        // Update part_id kalau sebelumnya null dan sekarang ada
        if ($partId && !$record->part_id) {
            $record->part_id = $partId;
        }

        $newQty = (float) $record->qty_on_hand + $qtyChange;

        if ($newQty < 0) {
            throw new \Exception("Cannot reduce stock below zero. Current: {$record->qty_on_hand}, Change: {$qtyChange}");
        }

        $record->update(['qty_on_hand' => $newQty]);
    }

    /**
     * Consume stock (decrement) with optional batch allocation.
     * Selalu pakai gci_part_id sebagai primary reference.
     * Consume dari stock FIFO (production_date terlama dulu).
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

        // Resolve gci_part_id dari part_id kalau belum ada
        if (!$gciPartId && $partId) {
            $part = Part::find($partId);
            if ($part && $part->gci_part_id) {
                $gciPartId = $part->gci_part_id;
            }
        }

        if (!$gciPartId) {
            throw new \Exception("gci_part_id wajib ada untuk consume stock. part_id={$partId}");
        }

        // Kalau batch specific, langsung kurangi
        if ($batchNo !== null) {
            self::updateStock($partId, $locationCode, -$qty, $batchNo, null, $gciPartId);
            return;
        }

        // FIFO consumption berdasarkan gci_part_id
        $remaining = $qty;

        $rows = self::query()
            ->where('gci_part_id', $gciPartId)
            ->where('location_code', $locationCode)
            ->where('qty_on_hand', '>', 0)
            ->orderByRaw('production_date IS NULL') // non-null first
            ->orderBy('production_date')
            ->orderBy('batch_no')
            ->lockForUpdate()
            ->get();

        foreach ($rows as $row) {
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
     * Get all locations for a specific part with stock.
     * Bisa pakai gci_part_id (preferred) atau part_id (legacy).
     */
    public static function getLocationsForPart(int $partId, ?int $gciPartId = null)
    {
        $q = self::query()
            ->where('qty_on_hand', '>', 0)
            ->with('location')
            ->orderBy('location_code')
            ->orderBy('batch_no');

        if ($gciPartId) {
            $q->where('gci_part_id', $gciPartId);
        } else {
            // Resolve gci_part_id dulu
            $part = Part::find($partId);
            if ($part && $part->gci_part_id) {
                $q->where('gci_part_id', $part->gci_part_id);
            } else {
                $q->where('part_id', $partId);
            }
        }

        return $q->get();
    }

    /**
     * Get stock summary for a GCI part across all locations.
     */
    public static function getStockSummary(int $partId, ?int $gciPartId = null): array
    {
        $q = self::query()->where('qty_on_hand', '>', 0);

        if ($gciPartId) {
            $q->where('gci_part_id', $gciPartId);
        } else {
            $part = Part::find($partId);
            if ($part && $part->gci_part_id) {
                $q->where('gci_part_id', $part->gci_part_id);
            } else {
                $q->where('part_id', $partId);
            }
        }

        $locations = $q->get();

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
