<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionMaterialRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'request_date' => 'date',
    ];

    public static function generateRequestNo(?string $date = null): string
    {
        $dateObj = Carbon::parse($date ?: now()->toDateString());
        $suffix = $dateObj->format('dmy');

        $lastRequest = static::where('request_no', 'like', 'PMR%' . $suffix)
            ->orderByRaw('LENGTH(request_no) DESC, request_no DESC')
            ->first();

        $nextSeq = 1;
        if ($lastRequest) {
            $seqStr = substr($lastRequest->request_no, 3, strlen($lastRequest->request_no) - 3 - strlen($suffix));
            $nextSeq = ((int) $seqStr) + 1;
        }

        return 'PMR' . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT) . $suffix;
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items()
    {
        return $this->hasMany(ProductionMaterialRequestItem::class, 'production_material_request_id');
    }
}
