<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Manual WH Send Entries
    |--------------------------------------------------------------------------
    |
    | Entries here are exposed by /api/subcounts/wh-to-send when the physical
    | document needs to be subcounted before a matching subcon order exists.
    |
    */
    'manual_wh_to_send_entries' => [
        [
            'key' => 'doc-2025-06-23-maz65643601-pg',
            'document_no' => '--[2025-06-23',
            'part_no' => 'MAZ65643601-PG',
            'part_name' => 'BRACKET,HANDLE - ALPHA 8 F',
            'qty_outstanding' => 5000,
            'uom' => 'PCE',
            'process_type' => 'PG',
        ],
        [
            'key' => 'doc-2025-11-20-mifa62123401-pg',
            'document_no' => '--[2025-11-20',
            'part_no' => 'MIFA62123401-PG',
            'part_name' => 'LEG FRAME - VT',
            'qty_outstanding' => 10000,
            'uom' => 'PCE',
            'process_type' => 'PG',
        ],
    ],
];
