<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MrpPurchasePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'mrp_run_id',
        'part_id',
        'plan_date',
        'eta_week',
        'order_week',
        'required_qty',
        'on_hand',
        'on_order',
        'incoming_stock',
        'net_required',
        'planned_order_rec',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'net_required' => 'decimal:4',
        'required_qty' => 'decimal:4',
        'planned_order_rec' => 'decimal:4',
    ];

    public function run()
    {
        return $this->belongsTo(MrpRun::class, 'mrp_run_id');
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'part_id');
    }

    /**
     * Get the adjusted on-hand stock (on-hand + incoming)
     */
    public function getAdjustedOnHandAttribute(): float
    {
        return (float) $this->on_hand + (float) $this->incoming_stock;
    }
}
