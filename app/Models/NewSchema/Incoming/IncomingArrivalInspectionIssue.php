<?php

namespace App\Models\NewSchema\Incoming;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingArrivalInspectionIssue extends BaseModel
{
    protected $table = 'incoming_arrival_inspection_issues';

    protected $fillable = [
        'inspection_id',
        'position',
        'description',
        'photo',
        'created_by',
        'updated_by',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(IncomingArrivalInspection::class, 'inspection_id');
    }
}