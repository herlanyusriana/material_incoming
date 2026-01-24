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
        'part_id',
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
     * Get the part
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
    public static function updateStock(int $partId, string $locationCode, float $qtyChange, ?string $batchNo = null, ?string $productionDate = null): void
    {
        $locationCode = strtoupper(trim($locationCode));
        $batchNo = $batchNo !== null ? strtoupper(trim($batchNo)) : null;
        if ($batchNo === '') {
            $batchNo = null;
        }

        $record = self::firstOrCreate(
            [
                'part_id' => $partId,
                'location_code' => $locationCode,
                'batch_no' => $batchNo,
            ],
            [
                'qty_on_hand' => 0,
                'production_date' => $productionDate ?: null,
            ]
        );

        $newQty = (float) $record->qty_on_hand + $qtyChange;

        if ($newQty < 0) {
            throw new \Exception("Cannot reduce stock below zero. Current: {$record->qty_on_hand}, Change: {$qtyChange}");
        }

        $record->update(['qty_on_hand' => $newQty]);
    }

    /**
     * Consume stock (decrement) with optional batch allocation.
     * If no batch specified, consumes FIFO by production_date (oldest first) then batch_no.
     */
    public static function consumeStock(int $partId, string $locationCode, float $qty, ?string $batchNo = null): void
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

        if ($batchNo !== null) {
            self::updateStock($partId, $locationCode, -$qty, $batchNo);
            return;
        }

        $remaining = $qty;

        $rows = self::query()
            ->where('part_id', $partId)
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
