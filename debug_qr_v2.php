<?php
require __DIR__ . '/vendor/autoload.php';

echo "Endroid Check:\n";
if (class_exists(\Endroid\QrCode\Builder\Builder::class)) {
    echo "Builder exists.\n";
    $r = new ReflectionMethod(\Endroid\QrCode\Builder\Builder::class, 'build');
    foreach ($r->getParameters() as $i => $p) {
        $t = $p->getType();
        echo "Arg $i: " . $p->getName() . " (" . ($t instanceof \ReflectionNamedType ? $t->getName() : (string) $t) . ")\n";
    }
}

echo "\nListing ErrorCorrectionLevel classes:\n";
// Try to find the file path for ErrorCorrectionLevelInterface
if (interface_exists('\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface')) {
    $r = new ReflectionClass('\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface');
    $dir = dirname($r->getFileName());
    echo "Dir: $dir\n";
    foreach (glob($dir . '/*.php') as $f) {
        echo basename($f) . "\n";
    }
} else {
    echo "Interface not found.\n";
    // Search recursively in src
    $src = __DIR__ . '/vendor/endroid/qr-code/src';
    if (is_dir($src)) {
        $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src));
        foreach ($iter as $file) {
            if ($file->isFile() && str_contains($file->getFilename(), 'ErrorCorrection')) {
                echo "Found: " . $file->getPathname() . "\n";
            }
        }
    }
}
