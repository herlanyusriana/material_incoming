<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'purchase_request_item_id',
        'part_id',
        'vendor_part_id',
        'gci_part_vendor_id',
        'qty',
        'unit_price',
        'subtotal',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseRequestItem()
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'part_id');
    }

    public function vendorPart()
    {
        return $this->belongsTo(Part::class, 'vendor_part_id');
    }

    public function gciPartVendor()
    {
        return $this->belongsTo(GciPartVendor::class);
    }
}
