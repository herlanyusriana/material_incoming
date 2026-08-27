<?php

namespace App\Models\NewSchema\Inventory;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Inventory\InventoryStockMovement;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryLocationStock extends BaseModel
{
    protected $table = 'inventory_location_stock';

    protected $fillable = [
        'gci_part_id',
        'location_code',
        'batch_no',
        'production_date',
        'qty_on_hand',
        'last_counted_at',
        'last_movement_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'production_date' => 'date',
        'last_counted_at' => 'datetime',
        'last_movement_at' => 'datetime',
        'qty_on_hand' => 'decimal:3',
    ];

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    /**
     * Update stock for a specific part/location/tag.
     * If a record doesn't exist, create it.
     */
    public static function updateStock(
        int $gciPartId,
        string $locationCode,
        float $qtyChange,
        ?string $batchNo = null,
        ?string $tag = null,
        string $transactionType = 'RECEIVE',
        ?string $sourceReference = null,
        ?int $sourceReceiveId = null,
        ?int $sourceArrivalId = null,
        ?string $sourceInvoiceNo = null,
        ?string $sourceDeliveryNoteNo = null,
        ?float $weightKgm = null,
        ?int $createdBy = null
    ): void {
        $record = self::where('gci_part_id', $gciPartId)
            ->where('location_code', $locationCode)
            ->where('batch_no', $batchNo ?? '')
            ->first();

        $before = $record ? (float) $record->qty_on_hand : 0.0;
        $after = max(0, $before + $qtyChange);

        if ($record) {
            $record->update([
                'qty_on_hand' => $after,
                'last_movement_at' => now(),
            ]);
        } else {
            self::create([
                'gci_part_id' => $gciPartId,
                'location_code' => $locationCode,
                'batch_no' => $batchNo ?? '',
                'qty_on_hand' => $after,
                'last_movement_at' => now(),
                'created_by' => $createdBy,
            ]);
        }

        // Log movement
        $isInbound = $qtyChange >= 0;
        $gciPart = GciPart::find($gciPartId);
        InventoryStockMovement::create([
            'gci_part_id' => $gciPartId,
            'part_id' => null,
            'tag_number' => $tag,
            'part_no' => $gciPart?->part_no,
            'part_name' => $gciPart?->part_name,
            'movement_type' => $transactionType,
            'uom' => null,
            'from_location_code' => $isInbound ? null : $locationCode,
            'to_location_code' => $isInbound ? $locationCode : null,
            'qty' => abs($qtyChange),
            'notes' => $sourceReference ? json_encode(['reference' => $sourceReference]) : null,
            'moved_at' => now(),
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Get stock quantity for a specific part/location/batch.
     */
    public static function getStockByLocation(
        int $gciPartId,
        string $locationCode,
        ?string $batchNo = null
    ): float {
        $query = self::where('gci_part_id', $gciPartId)
            ->where('location_code', $locationCode);

        if ($batchNo !== null) {
            $query->where('batch_no', $batchNo);
        }

        return (float) $query->sum('qty_on_hand');
    }

    /**
     * Consume stock (negative adjustment) for a specific part/location/batch.
     * This is a convenience wrapper around updateStock.
     */
    public static function consumeStock(
        int $gciPartId,
        string $locationCode,
        float $qty,
        ?string $batchNo = null,
        string $transactionType = 'DELIVERY',
        ?string $sourceReference = null,
        ?int $createdBy = null
    ): void {
        self::updateStock(
            $gciPartId,
            $locationCode,
            -$qty,
            $batchNo,
            null,
            $transactionType,
            $sourceReference,
            null,
            null,
            null,
            null,
            null,
            $createdBy
        );
    }

    /**
     * Get all locations (with stock) for a given part.
     * Returns a Collection of location_code and qty_on_hand.
     */
    public static function getLocationsForPart(int $gciPartId): Collection
    {
        return self::where('gci_part_id', $gciPartId)
            ->where('qty_on_hand', '>', 0)
            ->select('location_code', 'qty_on_hand')
            ->get();
    }
}