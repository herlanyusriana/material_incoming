<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class LocationInventory extends Model
{
    use HasFactory;

    protected $table = 'location_inventory';

    protected $fillable = [
        'gci_part_id',
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

    protected static function booted(): void
    {
        static::saved(function (LocationInventory $locationInventory) {
            $locationInventory->syncSummaryTables();
        });

        static::deleted(function (LocationInventory $locationInventory) {
            $locationInventory->syncSummaryTables();
        });
    }

    protected function syncSummaryTables(): void
    {
        if ($this->part_id) {
            $this->syncInventorySummary($this->part_id);
        }

        if ($this->gci_part_id) {
            $this->syncGciInventorySummary($this->gci_part_id);
        }
    }

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

    public function gciPart()
    {
        return $this->belongsTo(GciPart::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_code', 'location_code');
    }

    public static function getStockByLocation(int $partId, string $locationCode, ?string $batchNo = null, ?int $gciPartId = null): float
    {
        if (!$gciPartId) {
            $part = Part::find($partId);
            if ($part) {
                $gciPartId = $part->gci_part_id;
            } else {
                $gciPartId = GciPart::where('id', $partId)->value('id');
            }
        }

        $q = self::query()
            ->where('location_code', strtoupper(trim($locationCode)));

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

    public static function updateStock(?int $partId, string $locationCode, float $qtyChange, ?string $batchNo = null, ?string $productionDate = null, ?int $gciPartId = null, ?string $transactionType = null, ?string $sourceReference = null, array $traceability = []): void
    {
        $locationCode = strtoupper(trim($locationCode));
        $batchNo = $batchNo !== null ? strtoupper(trim($batchNo)) : null;
        if ($batchNo === '') {
            $batchNo = null;
        }

        if ($partId && !$gciPartId) {
            $part = Part::find($partId);
            if ($part && $part->gci_part_id) {
                $gciPartId = $part->gci_part_id;
            } else {
                $vendorPart = GciPartVendor::find($partId);
                if ($vendorPart && $vendorPart->gci_part_id) {
                    $gciPartId = $vendorPart->gci_part_id;
                }
            }

            if (!$gciPartId) {
                $gci = GciPart::find($partId);
                if ($gci) {
                    $gciPartId = $gci->id;
                    $partId = null;
                }
            }
        }

        if (!$gciPartId && !$partId) {
            throw new \Exception('part_id atau gci_part_id wajib ada untuk update inventory.');
        }

        if (!$gciPartId) {
            throw new RuntimeException('GCI Part belum terhubung, inventory lokasi tidak bisa diperbarui.');
        }

        $attributes = [
            'gci_part_id' => $gciPartId,
            'location_code' => $locationCode,
            'batch_no' => $batchNo,
        ];
        if ($partId) {
            $attributes['part_id'] = $partId;
        }

        $record = self::firstOrCreate(
            $attributes,
            [
                'qty_on_hand' => 0,
                'production_date' => $productionDate ?: null,
                'part_id' => $partId,
                'gci_part_id' => $gciPartId,
            ]
        );

        if ($partId && !$record->part_id) {
            $record->part_id = $partId;
        }
        if ($gciPartId && !$record->gci_part_id) {
            $record->gci_part_id = $gciPartId;
        }

        $qtyBefore = (float) $record->qty_on_hand;
        $newQty = $qtyBefore + $qtyChange;

        if ($newQty < 0) {
            throw new \Exception("Cannot reduce stock below zero. Current: {$record->qty_on_hand}, Change: {$qtyChange}");
        }

        $record->update(['qty_on_hand' => $newQty]);

        self::logTransaction($partId, $gciPartId, $locationCode, $batchNo, $qtyBefore, $newQty, $qtyChange, $transactionType, $sourceReference, $traceability);
    }

    public static function consumeStock(?int $partId, string $locationCode, float $qty, ?string $batchNo = null, ?int $gciPartId = null, ?string $transactionType = null, ?string $sourceReference = null, array $traceability = []): void
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

        if (!$gciPartId && $partId) {
            $part = Part::find($partId);
            if ($part && $part->gci_part_id) {
                $gciPartId = $part->gci_part_id;
            }
        }

        if (!$gciPartId && !$partId) {
            throw new \Exception('part_id atau gci_part_id wajib ada untuk consume stock.');
        }

        if ($batchNo !== null) {
            self::updateStock($partId, $locationCode, -$qty, $batchNo, null, $gciPartId, $transactionType, $sourceReference, $traceability);
            return;
        }

        $remaining = $qty;

        $rows = self::query()
            ->where('location_code', $locationCode)
            ->where('qty_on_hand', '>', 0)
            ->when($gciPartId, fn ($q) => $q->where('gci_part_id', $gciPartId))
            ->when(!$gciPartId, fn ($q) => $q->where('part_id', $partId))
            ->orderByRaw('production_date IS NULL')
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

            self::logTransaction(
                $partId ?: $row->part_id,
                $gciPartId ?: $row->gci_part_id,
                $locationCode,
                $row->batch_no,
                $available,
                $available - $take,
                -$take,
                $transactionType,
                $sourceReference,
                $traceability
            );
        }

        if ($remaining > 0) {
            throw new \Exception("Not enough stock at {$locationCode}. Need {$qty}, remaining {$remaining}.");
        }
    }

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
            $part = Part::find($partId);
            if ($part && $part->gci_part_id) {
                $q->where('gci_part_id', $part->gci_part_id);
            } else {
                $q->where('part_id', $partId);
            }
        }

        return $q->get();
    }

    protected static function logTransaction(?int $partId, ?int $gciPartId, string $locationCode, ?string $batchNo, float $qtyBefore, float $qtyAfter, float $qtyChange, ?string $transactionType = null, ?string $sourceReference = null, array $traceability = []): void
    {
        LocationInventoryAdjustment::create([
            'part_id' => $partId,
            'gci_part_id' => $gciPartId,
            'location_code' => $locationCode,
            'batch_no' => $batchNo,
            'source_receive_id' => $traceability['source_receive_id'] ?? null,
            'source_arrival_id' => $traceability['source_arrival_id'] ?? null,
            'source_invoice_no' => $traceability['source_invoice_no'] ?? null,
            'source_delivery_note_no' => $traceability['source_delivery_note_no'] ?? null,
            'source_tag' => $traceability['source_tag'] ?? ($batchNo ?: null),
            'action_type' => 'stock_movement',
            'transaction_type' => $transactionType,
            'source_reference' => $sourceReference,
            'qty_before' => $qtyBefore,
            'qty_after' => $qtyAfter,
            'qty_change' => $qtyChange,
            'adjusted_at' => now(),
            'created_by' => auth()->id(),
        ]);
    }

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
