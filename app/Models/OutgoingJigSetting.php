<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingJigSetting extends Model
{
    protected $table = 'outgoing_jig_settings';

    protected $fillable = [
        'line',
        'project_name',
        'uph',
    ];

    public function plans(): HasMany
    {
        return $this->hasMany(OutgoingJigPlan::class, 'jig_setting_id');
    }
}
