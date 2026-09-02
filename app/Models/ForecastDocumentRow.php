<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForecastDocumentRow extends Model
{
    protected $fillable = [
        'forecast_document_id',
        'customer_part_no',
        'customer_part_name',
        'gci_part_id',
        'mapping_status',
        'row_no',
        'quantities',
    ];

    protected $casts = [
        'quantities' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(ForecastDocument::class, 'forecast_document_id');
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }
}
