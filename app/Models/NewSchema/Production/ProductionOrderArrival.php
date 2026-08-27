<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Incoming\IncomingArrival;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderArrival extends BaseModel
{
    protected $table = 'production_order_arrivals';

    protected $fillable = [
        'production_order_id',
        'arrival_id',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function arrival(): BelongsTo
    {
        return $this->belongsTo(IncomingArrival::class, 'arrival_id');
    }
}