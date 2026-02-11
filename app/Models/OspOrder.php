<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OspOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_no',
        'customer_id',
        'gci_part_id',
        'bom_item_id',
        'qty_received_material',
        'qty_assembled',
        'qty_shipped',
        'received_date',
        'target_ship_date',
        'shipped_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'received_date' => 'date',
        'target_ship_date' => 'date',
        'shipped_date' => 'date',
        'qty_received_material' => 'decimal:4',
        'qty_assembled' => 'decimal:4',
        'qty_shipped' => 'decimal:4',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function gciPart()
    {
        return $this->belongsTo(GciPart::class);
    }

    public function bomItem()
    {
        return $this->belongsTo(BomItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
