<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpsHistory extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'parts_count',
        'weeks_generated',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
