<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForecastDocument extends Model
{
    protected $fillable = [
        'document_no',
        'source',
        'period_start',
        'period_end',
        'uploaded_by',
        'uploaded_at',
        'status',
        'total_rows',
        'mapped_rows',
        'unmapped_rows',
        'notes',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ForecastDocumentRow::class);
    }
}
