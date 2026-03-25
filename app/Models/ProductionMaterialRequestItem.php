<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionMaterialRequestItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'qty_requested' => 'float',
        'qty_issued' => 'float',
        'stock_on_hand' => 'float',
        'stock_on_order' => 'float',
    ];

    public function materialRequest()
    {
        return $this->belongsTo(ProductionMaterialRequest::class, 'production_material_request_id');
    }

    public function part()
    {
        return $this->belongsTo(Part::class, 'part_id');
    }
}
