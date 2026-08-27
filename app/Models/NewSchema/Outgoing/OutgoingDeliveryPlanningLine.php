<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingDeliveryPlanningLine extends BaseModel
{
    protected $table = 'outgoing_delivery_planning_lines';

    protected $fillable = [
        'daily_plan_cell_id',
        'sales_order_item_id',
        'gci_part_id',
        'planned_date',
        'qty_planned',
        'qty_picked',
        'qty_delivered',
        'status',
        'source_type',
        'source_reference',
        'notes',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'qty_planned' => 'decimal:4',
        'qty_picked' => 'decimal:4',
        'qty_delivered' => 'decimal:4',
    ];

    public function dailyPlanCell(): BelongsTo
    {
        return $this->belongsTo(OutgoingDailyPlanCell::class, 'daily_plan_cell_id');
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function pickingFgs(): HasMany
    {
        return $this->hasMany(OutgoingPickingFg::class);
    }

    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    public function scopePicked($query)
    {
        return $query->where('status', 'picked');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }
}