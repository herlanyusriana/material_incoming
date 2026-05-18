<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubconOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_no',
        'contract_no',
        'vendor_id',
        'rm_gci_part_id',
        'gci_part_id',
        'bom_item_id',
        'production_order_id',
        'production_order_number',
        'process_type',
        'source_process_name',
        'target_process_name',
        'qty_sent',
        'qty_received',
        'qty_rejected',
        'sent_date',
        'expected_return_date',
        'received_date',
        'status',
        'notes',
        'send_location_code',
        'weight_kgm',
        'sent_posted_at',
        'sent_posted_by',
        'created_by',
    ];

    protected $casts = [
        'sent_date' => 'date',
        'expected_return_date' => 'date',
        'received_date' => 'date',
        'sent_posted_at' => 'datetime',
        'qty_sent' => 'integer',
        'qty_received' => 'integer',
        'qty_rejected' => 'integer',
        'weight_kgm' => 'decimal:4',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function rmPart()
    {
        return $this->belongsTo(GciPart::class, 'rm_gci_part_id');
    }

    public function gciPart()
    {
        return $this->belongsTo(GciPart::class);
    }

    public function bomItem()
    {
        return $this->belongsTo(BomItem::class);
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function receives()
    {
        return $this->hasMany(SubconOrderReceive::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_posted_by');
    }

    public function getQtyOutstandingAttribute(): float
    {
        return (int) $this->qty_sent - (int) $this->qty_received - (int) $this->qty_rejected;
    }
}
