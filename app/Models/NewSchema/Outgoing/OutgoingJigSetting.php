<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\CustomerPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingJigSetting extends BaseModel
{
    protected $table = 'outgoing_jig_settings';

    protected $fillable = [
        'gci_part_id',
        'customer_part_id',
        'jig_code',
        'jig_name',
        'description',
        'status',
    ];

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function customerPart(): BelongsTo
    {
        return $this->belongsTo(CustomerPart::class);
    }

    public function jigPlans(): HasMany
    {
        return $this->hasMany(OutgoingJigPlan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}