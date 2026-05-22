<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubcountBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'subcon_order_id',
        'subcon_order_no',
        'subcount_no',
        'created_at_mobile',
        'received_at',
        'title',
        'part_info',
        'operator_name',
        'description',
        'total_net_weight_kg',
        'raw_payload',
    ];

    protected $casts = [
        'created_at_mobile' => 'datetime',
        'received_at' => 'datetime',
        'total_net_weight_kg' => 'decimal:4',
        'raw_payload' => 'array',
    ];

    public function records()
    {
        return $this->hasMany(SubcountPackagingRecord::class);
    }

    public function subconOrder()
    {
        return $this->belongsTo(SubconOrder::class);
    }
}
