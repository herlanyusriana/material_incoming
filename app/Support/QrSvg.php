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
                    ->writerOptions([])
                    ->data($data)
                    ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                    ->errorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow())
                    ->size($size)
                    ->margin($margin)
                    ->build()
                    ->getString();
            }

            // endroid/qr-code v6 style (new Builder()->build()).
            if (class_exists(\Endroid\QrCode\Builder\Builder::class)) {
                $builder = new \Endroid\QrCode\Builder\Builder();
                $result = $builder->build(
                    writer: new \Endroid\QrCode\Writer\SvgWriter(),
                    writerOptions: [],
                    validateResult: false,
                    data: $data,
                    encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
                    errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::Low,
                    size: $size,
                    margin: $margin
                );

                return $result->getString();
            }
        } catch (\Throwable) {
            // Intentionally swallow: we prefer rendering the label without QR over 500.
        }

        return '';
    }
}

