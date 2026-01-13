<?php

declare(strict_types=1);

namespace App\Support;

final class QrSvg
{
    public static function make(string $data, int $size = 160, int $margin = 0): string
    {
        $data = trim($data);
        if ($data === '') {
            return '';
        }

        try {
            // endroid/qr-code v4/v5 style (Builder::create()).
            if (class_exists(\Endroid\QrCode\Builder\Builder::class) && method_exists(\Endroid\QrCode\Builder\Builder::class, 'create')) {
                return \Endroid\QrCode\Builder\Builder::create()
                    ->writer(new \Endroid\QrCode\Writer\SvgWriter())
                    ->data($data)
                    ->size($size)
                    ->margin($margin)
                    ->build()
                    ->getString();
            }

            // endroid/qr-code v6 style (new Builder()->build()).
            if (class_exists(\Endroid\QrCode\Builder\Builder::class)) {
                $builder = new \Endroid\QrCode\Builder\Builder();
                $result = $builder->build(new \Endroid\QrCode\Writer\SvgWriter(), null, null, $data, null, null, $size, $margin);

                return $result->getString();
            }
        } catch (\Throwable) {
            // Intentionally swallow: we prefer rendering the label without QR over 500.
        }

        return '';
    }
}

