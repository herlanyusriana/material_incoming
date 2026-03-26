<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PricingMaster extends Model
{
    use HasFactory;

    protected $table = 'pricing_masters';

    protected $fillable = [
        'gci_part_id',
        'vendor_id',
        'customer_id',
        'price_type',
        'currency',
        'uom',
        'min_qty',
        'price',
        'effective_from',
        'effective_to',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'min_qty' => 'decimal:3',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public const PRICE_TYPES = [
        'purchase_price' => 'Purchase Price',
        'material_cost' => 'Material Cost',
        'processing_cost' => 'Processing Cost',
        'standard_cost' => 'Standard Cost',
        'selling_price' => 'Selling Price',
        'osp_price' => 'OSP Price',
        'subcon_price' => 'Subcon Price',
    ];

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function resolveCurrentPrice(
        int $gciPartId,
        string $priceType,
        array $filters = [],
        string|\DateTimeInterface|null $effectiveDate = null
    ): ?self {
        if ($gciPartId <= 0 || $priceType === '') {
            return null;
        }

        $effectiveOn = $effectiveDate
            ? Carbon::parse($effectiveDate)->toDateString()
            : now()->toDateString();

        $customerId = !empty($filters['customer_id']) ? (int) $filters['customer_id'] : null;
        $vendorId = !empty($filters['vendor_id']) ? (int) $filters['vendor_id'] : null;

        return static::query()
            ->where('gci_part_id', $gciPartId)
            ->where('price_type', $priceType)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $effectiveOn)
            ->where(function ($q) use ($effectiveOn) {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $effectiveOn);
            })
            ->where(function ($q) use ($customerId) {
                if ($customerId) {
                    $q->where('customer_id', $customerId)->orWhereNull('customer_id');
                    return;
                }

                $q->whereNull('customer_id');
            })
            ->where(function ($q) use ($vendorId) {
                if ($vendorId) {
                    $q->where('vendor_id', $vendorId)->orWhereNull('vendor_id');
                    return;
                }

                $q->whereNull('vendor_id');
            })
            ->orderByRaw('CASE WHEN customer_id IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN vendor_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }
}
