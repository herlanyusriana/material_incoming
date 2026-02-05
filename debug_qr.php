<?php
require __DIR__ . '/vendor/autoload.php';

try {
    if (!class_exists(\Endroid\QrCode\Builder\Builder::class)) {
        echo "Builder class not found.\n";
        exit;
    }

    $r = new ReflectionMethod(\Endroid\QrCode\Builder\Builder::class, 'build');
    echo "Builder::build parameters:\n";
    foreach ($r->getParameters() as $p) {
        $type = $p->getType();
        echo $p->getName() . ': ' . ($type instanceof \ReflectionNamedType ? $type->getName() : (string) $type) . "\n";
    }

    echo "\nErrorCorrectionLevelLow exists: " . (class_exists(\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow::class) ? 'YES' : 'NO') . "\n";

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
