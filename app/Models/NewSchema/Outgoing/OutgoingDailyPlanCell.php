<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Production\ProductionOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingDailyPlanCell extends BaseModel
{
    protected $table = 'outgoing_daily_plan_cells';

    protected $fillable = [
        'plan_row_id',
        'date',
        'qty',
        'production_order_id',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'qty' => 'decimal:4',
    ];

    public function planRow(): BelongsTo
    {
        return $this->belongsTo(OutgoingDailyPlanRow::class, 'plan_row_id');
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function deliveryPlanningLines(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryPlanningLine::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReleased($query)
    {
        return $query->where('status', 'released');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}