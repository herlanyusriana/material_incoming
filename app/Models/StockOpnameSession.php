<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOpnameSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'session_no',
        'name',
        'status',
        'start_date',
        'end_date',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class, 'session_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }

    public static function generateSessionNo()
    {
        $date = date('Ymd');
        $last = self::whereDate('created_at', date('Y-m-d'))->count();
        $next = str_pad($last + 1, 3, '0', STR_PAD_LEFT);
        return "SO-{$date}-{$next}";
    }
}
