<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderActivity extends BaseModel
{
    protected $table = 'production_order_activities';

    protected $fillable = [
        'production_order_id',
        'action',
        'description',
        'metadata',
        'performed_by',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}