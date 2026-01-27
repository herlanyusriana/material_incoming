<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StockAtCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'period',
        'customer_id',
        'gci_part_id',
        'part_no',
        'part_name',
        'model',
        'status',
        'day_1',
        'day_2',
        'day_3',
        'day_4',
        'day_5',
        'day_6',
        'day_7',
        'day_8',
        'day_9',
        'day_10',
        'day_11',
        'day_12',
        'day_13',
        'day_14',
        'day_15',
        'day_16',
        'day_17',
        'day_18',
        'day_19',
        'day_20',
        'day_21',
        'day_22',
        'day_23',
        'day_24',
        'day_25',
        'day_26',
        'day_27',
        'day_28',
        'day_29',
        'day_30',
        'day_31',
    ];

    protected $casts = [
        'day_1' => 'decimal:3',
        'day_2' => 'decimal:3',
        'day_3' => 'decimal:3',
        'day_4' => 'decimal:3',
        'day_5' => 'decimal:3',
        'day_6' => 'decimal:3',
        'day_7' => 'decimal:3',
        'day_8' => 'decimal:3',
        'day_9' => 'decimal:3',
        'day_10' => 'decimal:3',
        'day_11' => 'decimal:3',
        'day_12' => 'decimal:3',
        'day_13' => 'decimal:3',
        'day_14' => 'decimal:3',
        'day_15' => 'decimal:3',
        'day_16' => 'decimal:3',
        'day_17' => 'decimal:3',
        'day_18' => 'decimal:3',
        'day_19' => 'decimal:3',
        'day_20' => 'decimal:3',
        'day_21' => 'decimal:3',
        'day_22' => 'decimal:3',
        'day_23' => 'decimal:3',
        'day_24' => 'decimal:3',
        'day_25' => 'decimal:3',
        'day_26' => 'decimal:3',
        'day_27' => 'decimal:3',
        'day_28' => 'decimal:3',
        'day_29' => 'decimal:3',
        'day_30' => 'decimal:3',
        'day_31' => 'decimal:3',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    public function qtyForDate(Carbon $date): float
    {
        $period = $date->format('Y-m');
        if ((string) ($this->period ?? '') !== $period) {
            return 0.0;
        }
        $day = (int) $date->format('j');
        if ($day < 1 || $day > 31) {
            return 0.0;
        }
        return (float) ($this->{'day_' . $day} ?? 0);
    }
}
