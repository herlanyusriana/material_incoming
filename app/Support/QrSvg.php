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

