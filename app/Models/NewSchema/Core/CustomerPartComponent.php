<?php

namespace App\Models\NewSchema\Core;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPartComponent extends BaseModel
{
    protected $table = 'customer_part_components';

    protected $fillable = [
        'customer_part_id',
        'gci_part_id',
        'qty_per_unit',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'qty_per_unit' => 'decimal:4',
    ];

    public function customerPart(): BelongsTo
    {
        return $this->belongsTo(CustomerPart::class);
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }
}