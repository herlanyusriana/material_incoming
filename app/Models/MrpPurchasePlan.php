<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MrpPurchasePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'mrp_run_id',
        'part_id',
        'required_qty',
        'on_hand',
        'on_order',
        'net_required',
    ];

    public function run()
    {
        return $this->belongsTo(MrpRun::class, 'mrp_run_id');
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'part_id');
    }
}
