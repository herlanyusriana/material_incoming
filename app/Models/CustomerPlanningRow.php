<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPlanningRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_id',
        'customer_part_no',
        'minggu',
        'qty',
        'part_id',
        'row_status',
        'error_message',
    ];

    public function planningImport()
    {
        return $this->belongsTo(CustomerPlanningImport::class, 'import_id');
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'part_id');
    }
}
