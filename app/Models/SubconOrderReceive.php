<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubconOrderReceive extends Model
{
    use HasFactory;

    protected $fillable = [
        'subcon_order_id',
        'qty_good',
        'qty_rejected',
        'received_date',
        'receive_location_code',
        'posted_to_wh_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'received_date' => 'date',
        'posted_to_wh_at' => 'datetime',
        'qty_good' => 'decimal:4',
        'qty_rejected' => 'decimal:4',
    ];

    public function subconOrder()
    {
        return $this->belongsTo(SubconOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
