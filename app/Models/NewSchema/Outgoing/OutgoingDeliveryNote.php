<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\Customer;
use App\Models\NewSchema\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingDeliveryNote extends BaseModel
{
    protected $table = 'outgoing_delivery_notes';

    protected $fillable = [
        'dn_no',
        'transaction_no',
        'customer_id',
        'delivery_date',
        'planned_delivery_date',
        'driver_id',
        'truck_id',
        'status',
        'invoice_file',
        'packing_list_file',
        'notes',
        'delivery_address',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'planned_delivery_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryNoteItem::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryOrder::class);
    }

    public function requirementFulfillments(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryRequirementFulfillment::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePicked($query)
    {
        return $query->where('status', 'picked');
    }

    public function scopeLoaded($query)
    {
        return $query->where('status', 'loaded');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }
}