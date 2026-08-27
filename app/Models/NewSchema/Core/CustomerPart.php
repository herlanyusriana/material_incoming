<?php

namespace App\Models\NewSchema\Core;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPart extends BaseModel
{
    protected $table = 'customer_parts';

    protected $fillable = [
        'customer_id',
        'part_no',
        'part_name',
        'description',
        'created_by',
        'updated_by',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(CustomerPartComponent::class);
    }

    public function salesOrderItems(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function outgoingDailyPlanRows(): HasMany
    {
        return $this->hasMany(OutgoingDailyPlanRow::class);
    }
}