<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Truck extends BaseModel
{
    protected $table = 'trucks';

    protected $fillable = [
        'license_plate',
        'truck_type',
        'capacity',
        'trucking_company_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function truckingCompany(): BelongsTo
    {
        return $this->belongsTo(TruckingCompany::class);
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryNote::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}