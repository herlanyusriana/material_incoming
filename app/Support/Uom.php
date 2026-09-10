<?php

namespace App\Support;

/**
 * UOM normalization — single canonical set.
 *
 * Aturan stok: satu part = satu UOM stok (gci_parts.uom). Tidak ada konversi
 * runtime; konversi PCS<->KGM adalah keputusan engineering di BOM
 * (consumption_qty + consumption_uom).
 */
final class Uom
{
    /** Kode kanonik -> alias yang pernah dipakai modul manapun. */
    private const ALIASES = [
        'PCE' => ['PCE', 'PCS', 'PC', 'EA', 'UNIT'],
        'KGM' => ['KGM', 'KG', 'KGS', 'KILO'],
        'SHEET' => ['SHEET'],
        'ROLL' => ['ROLL'],
        'SET' => ['SET'],
        'COIL' => ['COIL'],
        'BOX' => ['BOX'],
    ];

    /** Unit kemasan yang tidak boleh jadi UOM stok. */
    public const PACKAGING = ['COIL', 'PALLET', 'BUNDLE', 'BOX', 'BAG', 'PACKAGES'];

    public static function canonical(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return null;
        }

        foreach (self::ALIASES as $canonical => $aliases) {
            if (in_array($code, $aliases, true)) {
                return $canonical;
            }
        }

        // Kode tidak dikenal: kembalikan apa adanya (uppercase) supaya tidak
        // hilang saat edit, tetap bisa dipetakan belakangan.
        return $code;
    }

    public static function isPackaging(?string $code): bool
    {
        return in_array(self::canonical($code), self::PACKAGING, true);
    }

    /** PCS dan PCE adalah satuan yang sama. */
    public static function equivalent(?string $a, ?string $b): bool
    {
        $a = self::canonical($a);
        $b = self::canonical($b);

        return $a !== null && $a === $b;
    }
}
