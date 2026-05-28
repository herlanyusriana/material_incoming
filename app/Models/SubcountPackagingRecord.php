<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubcountPackagingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'subcount_batch_id',
        'external_id',
        'created_at_mobile',
        'packaging_id',
        'packaging_type',
        'packaging_qty',
        'packaging_weight_kg',
        'gross_weight_kg',
        'net_item_weight_kg',
        'description',
        'packaging_photo_path',
        'gross_photo_path',
    ];

    protected $casts = [
        'created_at_mobile' => 'datetime',
        'packaging_qty' => 'integer',
        'packaging_weight_kg' => 'decimal:4',
        'gross_weight_kg' => 'decimal:4',
        'net_item_weight_kg' => 'decimal:4',
    ];

    public function batch()
    {
        return $this->belongsTo(SubcountBatch::class, 'subcount_batch_id');
    }
}
