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
            'view_incoming',
            'create_production_entry', // Example: Scanning incoming materials
            'manage_incoming',
            'manage_subcounts',
        ],
        'ppic' => [
            'view_dashboard',
            'manage_planning',
            'view_production',
            'view_logistics',
            'manage_subcon',
        ],
        'warehouse' => [
            'view_dashboard',
            'view_incoming',
            'view_logistics',
            'manage_incoming',
            'manage_warehouse',
            'manage_subcounts',
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
        'view_incoming',       // View incoming material dashboard
        'manage_incoming',     // Scan arrival, print QR
        'manage_warehouse',    // Warehouse operations (QC, putaway, transfer, return, adjustment)
        'manage_subcounts',    // Manage subcounts

        // Logistics
        'view_logistics',      // View logistics dashboard

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
