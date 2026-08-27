<?php

namespace App\Models\NewSchema\Incoming;

use App\Models\NewSchema\BaseModel;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingReceive extends BaseModel
{
    protected $table = 'incoming_receives';

    protected $fillable = [
        'arrival_item_id',
        'tag',
        'qty',
        'bundle_qty',
        'unit_goods',
        'unit_bundle',
        'weight',
        'net_weight',
        'gross_weight',
        'weight_kgm',
        'location_code',
        'qc_status',
        'qc_note',
        'qc_updated_at',
        'qc_updated_by',
        'jo_po_number',
        'ata_date',
        'qc_audited_at',
        'qc_audited_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'qty' => 'integer',
        'weight' => 'decimal:2',
        'ata_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $receive) {
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

    public function arrivalItem(): BelongsTo
    {
        return $this->belongsTo(IncomingArrivalItem::class, 'arrival_item_id');
    }

    public function incomingArrivalItem(): BelongsTo
    {
        return $this->belongsTo(IncomingArrivalItem::class, 'arrival_item_id');
    }

    public function qcUpdater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qc_updated_by');
    }

    public function scopePass($query)
    {
        return $query->where('qc_status', 'pass');
    }

    public function scopeFail($query)
    {
        return $query->where('qc_status', 'fail');
    }

    public function scopeHold($query)
    {
        return $query->where('qc_status', 'hold');
    }
}