<?php

namespace App\Models\NewSchema\Incoming;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IncomingArrival extends BaseModel
{
    protected $table = 'incoming_arrivals';

    protected $fillable = [
        'arrival_no',
        'transaction_no',
        'invoice_no',
        'invoice_date',
        'vendor_id',
        'trucking_company_id',
        'vessel',
        'etd',
        'eta',
        'eta_gci',
        'bill_of_lading',
        'pen_no',
        'pen_date',
        'aju_no',
        'bill_of_lading_status',
        'bill_of_lading_file',
        'delivery_note_file',
        'invoice_file',
        'packing_list_file',
        'price_term',
        'hs_code',
        'hs_codes',
        'port_of_loading',
        'country',
        'container_numbers',
        'currency',
        'notes',
        'status',
        'created_by',
        'updated_by',
        'purchase_order_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'etd' => 'date',
        'eta' => 'date',
        'eta_gci' => 'date',
        'pen_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $arrival) {
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
            $lastSequence = (int) ($parts[2] ?? 0);
        }

        $next = str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);

        return 'ARR-' . $year . '-' . $next;
    }

    /**
     * Generate a unique transaction number for completed receives.
     * Format: SO{4-digit sequence per day}{DDMMYY} — 12 characters total
     * Example: SO1234010226
     */
    public static function generateTransactionNo(string $receiveDate): string
    {
        $date = Carbon::parse($receiveDate);
        $dateStr = $date->format('dmy');
        $suffix = $dateStr;

        // Count existing transaction_no for the same date
        $lastArrival = self::where('transaction_no', 'like', 'SO%' . $suffix)
            ->orderByRaw('LENGTH(transaction_no) DESC, transaction_no DESC')
            ->first();

        $nextSeq = 1;
        if ($lastArrival) {
            // Extract the sequence number between 'SO' and the date suffix
            $seqStr = substr($lastArrival->transaction_no, 2, strlen($lastArrival->transaction_no) - 2 - strlen($suffix));
            $nextSeq = ((int) $seqStr) + 1;
        }

        return 'SO' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT) . $suffix;
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\NewSchema\Core\User::class, 'created_by');
    }

    public function trucking(): BelongsTo
    {
        return $this->belongsTo(\App\Models\NewSchema\Outgoing\TruckingCompany::class, 'trucking_company_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(IncomingArrivalItem::class, 'arrival_id');
    }

    public function containers(): HasMany
    {
        return $this->hasMany(IncomingArrivalContainer::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(IncomingArrivalInspection::class);
    }

    /**
     * Primary inspection for this arrival (one row per arrival).
     */
    public function inspection(): HasOne
    {
        return $this->hasOne(IncomingArrivalInspection::class);
    }

    public function receives(): HasMany
    {
        return $this->hasMany(IncomingReceive::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}