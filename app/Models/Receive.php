<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class Receive extends Model
{
    protected $fillable = [
        'arrival_item_id',
        'tag',
        'qty',
        'bundle_unit',
        'bundle_qty',
        'ata_date',
        'qc_status',
        'qc_note',
        'qc_updated_at',
        'qc_updated_by',
        'weight',
        'net_weight',
        'gross_weight',
        'qty_unit',
        'jo_po_number',
        'invoice_no',
        'delivery_note_no',
        'truck_no',
        'location_code',
    ];

    protected $casts = [
        'ata_date' => 'datetime',
        'qc_updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Receive $receive) {
            if (!is_string($receive->tag) || trim($receive->tag) === '') {
                $receive->forceFill([
                    'tag' => self::generateSystemTag(
                        (int) $receive->id,
                        $receive->ata_date instanceof CarbonInterface ? $receive->ata_date : null
                    ),
                ])->saveQuietly();
            }
        });
    }

    public static function generateSystemTag(int $receiveId, ?CarbonInterface $date = null): string
    {
        $tagDate = $date ?? now();

        return sprintf(
            'RCV-%s-%06d',
            $tagDate->format('ymd'),
            max(1, $receiveId)
        );
    }

    public function ensureSystemTag(): string
    {
        $tag = is_string($this->tag) ? strtoupper(trim($this->tag)) : '';
        if ($tag !== '') {
            return $tag;
        }

        $tag = self::generateSystemTag(
            (int) $this->id,
            $this->ata_date instanceof CarbonInterface ? $this->ata_date : null
        );

        $this->forceFill(['tag' => $tag])->saveQuietly();

        return $tag;
    }

    public function arrivalItem()
    {
        return $this->belongsTo(ArrivalItem::class);
    }

    public function qcUpdater()
    {
        return $this->belongsTo(User::class, 'qc_updated_by');
    }
}
