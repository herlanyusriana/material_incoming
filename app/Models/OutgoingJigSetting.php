<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingJigSetting extends Model
{
    protected $table = 'outgoing_jig_settings';

    protected $fillable = [
        'line',
        'customer_part_id',
        'uph',
    ];

    public function customerPart()
    {
        return $this->belongsTo(CustomerPart::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(OutgoingJigPlan::class, 'jig_setting_id');
    }
}
