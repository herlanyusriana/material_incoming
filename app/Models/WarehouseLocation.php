<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_code',
        'class',
        'zone',
        'qr_payload',
        'status',
    ];

    private static function upperOrNull(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        return $string === '' ? null : strtoupper($string);
    }

    public static function buildPayload(string $locationCode, ?string $class = null, ?string $zone = null): string
    {
        $payload = [
            'type' => 'WAREHOUSE_LOCATION',
            'location_code' => strtoupper(trim($locationCode)),
            'class' => self::upperOrNull($class),
            'zone' => self::upperOrNull($zone),
        ];

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }

    protected function locationCode(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => self::upperOrNull($value),
        );
    }

    protected function class(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => self::upperOrNull($value),
        );
    }

    protected function zone(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => self::upperOrNull($value),
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => self::upperOrNull($value) ?? 'ACTIVE',
        );
    }
}

