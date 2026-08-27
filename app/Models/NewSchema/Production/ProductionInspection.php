<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionInspection extends BaseModel
{
    protected $table = 'production_inspections';

    protected $fillable = [
        'production_order_id',
        'inspection_no',
        'inspection_date',
        'type',
        'result',
        'inspector_name',
        'notes',
        'inspected_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function scopePass($query)
    {
        return $query->where('result', 'pass');
    }

    public function scopeFail($query)
    {
        return $query->where('result', 'fail');
    }
}