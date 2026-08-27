<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\CustomerPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingDailyPlanRow extends BaseModel
{
    protected $table = 'outgoing_daily_plan_rows';

    protected $fillable = [
        'plan_id',
        'row_no',
        'gci_part_id',
        'customer_part_id',
        'daily_quantities',
        'total_qty',
        'sales_order_id',
        'notes',
    ];

    protected $casts = [
        'daily_quantities' => 'json',
        'total_qty' => 'decimal:4',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(OutgoingDailyPlan::class, 'plan_id');
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function customerPart(): BelongsTo
    {
        return $this->belongsTo(CustomerPart::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function cells(): HasMany
    {
        return $this->hasMany(OutgoingDailyPlanCell::class);
    }
}