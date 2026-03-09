<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StockAtCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_date',
        'customer_id',
        'gci_part_id',
        'part_no',
        'part_name',
        'model',
        'status',
        'qty',
    ];

    protected $casts = [
        'stock_date' => 'date',
        'qty' => 'decimal:3',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    /**
     * Get stock qty for a specific date (convenience — works on any record).
     */
    public function qtyForDate(Carbon $date): float
    {
        if ($this->stock_date && $this->stock_date->format('Y-m-d') === $date->format('Y-m-d')) {
            return (float) ($this->qty ?? 0);
        }
        return 0.0;
    }

    /**
     * Scope: records within a given period (YYYY-MM).
     */
    public function scopeForPeriod($query, string $period)
    {
        $year = (int) substr($period, 0, 4);
        $month = (int) substr($period, 5, 2);
        $start = sprintf('%04d-%02d-01', $year, $month);
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $end = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

        return $query->whereBetween('stock_date', [$start, $end]);
    }
}
