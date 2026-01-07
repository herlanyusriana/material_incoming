<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GciPart extends Model
{
    use HasFactory;

    protected $table = 'gci_parts';

    protected $fillable = [
        'part_no',
        'part_name',
        'status',
    ];

    private static function upperOrNull(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        return strtoupper($string);
    }

    protected function partNo(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => self::upperOrNull($value),
        );
    }

    protected function partName(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === null ? null : trim((string) $value),
        );
    }

    public function forecasts()
    {
        return $this->hasMany(Forecast::class, 'part_id');
    }

    public function mps()
    {
        return $this->hasMany(Mps::class, 'part_id');
    }

    public function bom()
    {
        return $this->hasOne(Bom::class, 'part_id');
    }
}
