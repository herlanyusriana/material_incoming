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
        'qty_on_hand',
        'last_counted_at',
    ];

    protected $casts = [
        'qty_on_hand' => 'decimal:4',
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
    public static function getStockByLocation(int $partId, string $locationCode): float
    {
        $record = self::where('part_id', $partId)
            ->where('location_code', $locationCode)
            ->first();

        return $record ? (float) $record->qty_on_hand : 0;
    }

    /**
     * Update stock for a part at a location
     * Positive qtyChange = increase, Negative = decrease
     */
    public static function updateStock(int $partId, string $locationCode, float $qtyChange): void
    {
        $record = self::firstOrCreate(
            [
                'part_id' => $partId,
                'location_code' => $locationCode,
            ],
            [
                'qty_on_hand' => 0,
            ]
        );

        $newQty = (float) $record->qty_on_hand + $qtyChange;

        if ($newQty < 0) {
            throw new \Exception("Cannot reduce stock below zero. Current: {$record->qty_on_hand}, Change: {$qtyChange}");
        }

        $record->update(['qty_on_hand' => $newQty]);
    }

    /**
     * Get all locations for a specific part with stock
     */
    public static function getLocationsForPart(int $partId)
    {
        return self::where('part_id', $partId)
            ->where('qty_on_hand', '>', 0)
            ->with('location')
            ->orderBy('location_code')
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
