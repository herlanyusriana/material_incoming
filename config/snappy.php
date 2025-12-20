<?php

return [
    'pdf' => [
        'enabled' => true,
        'binary' => env('WKHTML_PDF_BINARY', '/usr/bin/wkhtmltopdf'),
        'timeout' => false,
        'options' => [
            'encoding' => 'UTF-8',
        ],
        'env' => [],
    ],

    'image' => [
        'enabled' => true,
        'binary' => env('WKHTML_IMG_BINARY', '/usr/bin/wkhtmltoimage'),
        'timeout' => false,
        'options' => [],
        'env' => [],
    ],
];

