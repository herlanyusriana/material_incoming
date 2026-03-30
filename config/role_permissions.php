<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role Permissions Matrix
    |--------------------------------------------------------------------------
    |
    | This file defines the permissions for each role in the application.
    | The 'admin' role generally has access to everything ('*').
    |
    */

    'roles' => [
        'admin' => [
            '*', // All permissions
        ],
        'staff' => [
            'view_dashboard',
            'view_planning',
            'view_production',
            'create_production_entry', // Example: Scanning incoming materials
            'manage_incoming',
        ],
        'ppic' => [
            'view_dashboard',
            'manage_planning',
            'view_production',
            'manage_subcon',
        ],
        'warehouse' => [
            'view_dashboard',
            'manage_incoming',
        ],
        'purchasing' => [
            'view_dashboard',
            'manage_purchasing',
        ],
        'quality' => [
            'view_dashboard',
            'view_production',
            'manage_qc_inspection',
            'manage_in_process_inspection',
            'manage_final_inspection',
            'manage_kanban_update',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Definitions
    |--------------------------------------------------------------------------
    |
    | List of all available permissions for reference.
    |
    */
    'defined_permissions' => [
        'view_dashboard',

        // Planning Module
        'view_planning',
        'manage_planning',     // Create forecasts, run MRP, import data
        'delete_planning',     // Clear data

        // Production Module
        'view_production',
        'manage_production',   // Create orders
        'manage_qc_inspection',
        'manage_in_process_inspection',
        'manage_final_inspection',
        'manage_kanban_update',

        // Material Incoming / Warehouse
        'manage_incoming',     // Scan arrival, print QR

        // Purchasing
        'manage_purchasing',

        // Master Data
        'manage_users',
        'manage_parts',
        'manage_customers',

        // Outgoing
        'manage_outgoing',

        // Subcon
        'manage_subcon',

        // Inventory/Warehouse
        'manage_inventory',
    ],
];
