<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MrpHistory extends Model
{
    protected $fillable = [
        'mrp_run_id',
        'user_id',
        'action',
        'parts_count',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mrpRun()
    {
        return $this->belongsTo(MrpRun::class);
    }
}
