<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutgoingDeliveryPlanningLine extends Model
{
    protected $table = 'outgoing_delivery_planning_lines';

    protected $fillable = [
        'delivery_date',
        'gci_part_id',
        'trip_1',
        'trip_2',
        'trip_3',
        'trip_4',
        'trip_5',
        'trip_6',
        'trip_7',
        'trip_8',
        'trip_9',
        'trip_10',
        'trip_11',
        'trip_12',
        'trip_13',
        'trip_14',
        'notes',
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    /**
     * Sum of all trip columns.
     */
    public function getTotalTripsAttribute(): int
    {
        $total = 0;
        for ($i = 1; $i <= 14; $i++) {
            $total += (int) ($this->{"trip_{$i}"} ?? 0);
        }
        return $total;
    }
}
