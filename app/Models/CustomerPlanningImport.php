<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPlanningImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'file_name',
        'uploaded_by',
        'status',
        'total_rows',
        'accepted_rows',
        'rejected_rows',
        'notes',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function rows()
    {
        return $this->hasMany(CustomerPlanningRow::class, 'import_id');
    }
}
