<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\Customer;
use App\Models\NewSchema\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends BaseModel
{
    protected $table = 'sales_orders';

    protected $fillable = [
        'so_no',
        'customer_id',
        'order_date',
        'delivery_date',
        'po_customer_no',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function dailyPlanRows(): HasMany
    {
        return $this->hasMany(OutgoingDailyPlanRow::class);
    }

    public function pickingFgs(): HasMany
    {
        return $this->hasMany(OutgoingPickingFg::class);
    }

    public function deliveryRequirements(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryRequirement::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}