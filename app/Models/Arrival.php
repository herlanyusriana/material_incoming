<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arrival extends Model
{
    use HasFactory;

    protected $fillable = [
        'arrival_no',
        'invoice_no',
        'invoice_date',
        'vendor_id',
        'trucking_company_id',
        'vessel',
        'trucking_company',
        'ETD',
        'bill_of_lading',
        'hs_code',
        'port_of_loading',
        'country',
        'container_numbers',
        'currency',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'ETD' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Arrival $arrival) {
            if (empty($arrival->arrival_no)) {
                $arrival->arrival_no = self::generateArrivalNo();
            }
        });
    }

    public static function generateArrivalNo(): string
    {
        $year = Carbon::now()->year;
        $lastArrival = self::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $lastSequence = 0;
        if ($lastArrival) {
            $parts = explode('-', $lastArrival->arrival_no);
            $lastSequence = (int)($parts[2] ?? 0);
        }

        $next = str_pad((string)($lastSequence + 1), 4, '0', STR_PAD_LEFT);

        return 'ARR-' . $year . '-' . $next;
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function trucking()
    {
        return $this->belongsTo(Trucking::class, 'trucking_company_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(ArrivalItem::class);
    }
}
