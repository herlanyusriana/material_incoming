<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Arrival extends Model
{
    use HasFactory;

    protected $fillable = [
        'arrival_no',
        'transaction_no',
        'invoice_no',
        'invoice_date',
        'vendor_id',
        'trucking_company_id',
        'vessel',
        'trucking_company',
        'ETD',
        'ETA',
        'ETA_GCI',
        'bill_of_lading',
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
        'created_by',
        'purchase_order_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'ETD' => 'date',
        'ETA' => 'date',
        'ETA_GCI' => 'date',
    ];

    public function getBillOfLadingFileUrlAttribute(): ?string
    {
        if (!$this->bill_of_lading_file) {
            return null;
        }
        return Storage::disk('public')->url($this->bill_of_lading_file);
    }

    public function getDeliveryNoteFileUrlAttribute(): ?string
    {
        if (!$this->delivery_note_file) {
            return null;
        }
        return Storage::disk('public')->url($this->delivery_note_file);
    }

    public function getInvoiceFileUrlAttribute(): ?string
    {
        if (!$this->invoice_file) {
            return null;
        }
        return Storage::disk('public')->url($this->invoice_file);
    }

    public function getPackingListFileUrlAttribute(): ?string
    {
        if (!$this->packing_list_file) {
            return null;
        }
        return Storage::disk('public')->url($this->packing_list_file);
    }

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

    public function containers()
    {
        return $this->hasMany(ArrivalContainer::class);
    }

    public function inspection()
    {
        return $this->hasOne(ArrivalInspection::class);
    }

    /**
     * Linked production orders (WO) for traceability: SO ↔ WO
     */
    public function productionOrders()
    {
        return $this->belongsToMany(ProductionOrder::class, 'production_order_arrivals')
            ->withTimestamps();
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
