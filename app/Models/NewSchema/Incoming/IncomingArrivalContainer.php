<?php

namespace App\Models\NewSchema\Incoming;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IncomingArrivalContainer extends BaseModel
{
    protected $table = 'incoming_arrival_containers';

    protected $fillable = [
        'arrival_id',
        'container_no',
        'seal_no',
        'created_by',
        'updated_by',
    ];

    public function arrival(): BelongsTo
    {
        return $this->belongsTo(IncomingArrival::class, 'arrival_id');
    }

    public function inspection(): HasOne
    {
        return $this->hasOne(IncomingArrivalContainerInspection::class, 'arrival_container_id');
    }
}