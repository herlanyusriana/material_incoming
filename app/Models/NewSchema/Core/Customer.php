<?php

namespace App\Models\NewSchema\Core;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends BaseModel
{
    protected $table = 'customers';

    protected $fillable = [
        'code',
        'name',
        'status',
        'created_by',
        'updated_by',
    ];

    public function customerParts(): HasMany
    {
        return $this->hasMany(CustomerPart::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function outgoingDeliveryNotes(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryNote::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}