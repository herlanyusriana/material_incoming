<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutgoingPo extends Model
{
    protected $table = 'outgoing_pos';

    protected $fillable = [
        'po_no',
        'customer_id',
        'po_release_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'po_release_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OutgoingPoItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalQtyAttribute(): int
    {
        return $this->items->sum('qty');
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->items->sum(fn($i) => $i->qty * $i->price);
    }

    public function scopeConfirmedWithPendingDelivery($query)
    {
        return $query->where('status', 'confirmed')
            ->whereHas('items', function ($q) {
                $q->whereColumn('fulfilled_qty', '<', 'qty')
                    ->whereNotNull('gci_part_id');
            });
    }
}
