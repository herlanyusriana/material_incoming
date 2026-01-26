<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'dn_id',
        'gci_part_id',
        'customer_po_id',
        'qty',
        'picked_qty',
        'picked_at',
        'picked_by',
        'kitting_location_code',
    ];

    protected $casts = [
        'picked_at' => 'datetime',
    ];

    public function deliveryNote()
    {
        return $this->belongsTo(DeliveryNote::class, 'dn_id');
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    public function customerPo()
    {
        return $this->belongsTo(CustomerPo::class, 'customer_po_id');
    }

    public function picker()
    {
        return $this->belongsTo(User::class, 'picked_by');
    }
}
