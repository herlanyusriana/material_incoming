<?php

/*
|--------------------------------------------------------------------------
| Modules & Menu Configuration — Single Source of Truth
|--------------------------------------------------------------------------
|
| Full-launcher navigation: Home grid reads this, module dashboards read
| their own section, RBAC filters items, and Role Management groups its
| permission checklist by this structure (K1+K2 consistency).
|
| item.route: existing named route, or null => feature "coming soon"
| (built in later phases; tile rendered disabled).
|
*/

return [

    'modules' => [

        'master-data' => [
            'label' => 'modules.master_data',
            'icon' => 'database',
            'color' => 'indigo',
            'items' => [
                ['key' => 'parts', 'label' => 'modules.parts_master', 'icon' => 'cube', 'route' => 'parts.index', 'permission' => 'manage_parts'],
                ['key' => 'customers', 'label' => 'modules.customer_master', 'icon' => 'users', 'route' => 'planning.customers.index', 'permission' => 'manage_planning'],
                ['key' => 'vendors', 'label' => 'modules.vendor_master', 'icon' => 'building-store', 'route' => 'vendors.index', 'permission' => 'manage_parts'],
                ['key' => 'truckings', 'label' => 'modules.trucking_company', 'icon' => 'truck', 'route' => 'truckings.index', 'permission' => 'manage_parts'],
                ['key' => 'machines', 'label' => 'modules.machine', 'icon' => 'chip', 'route' => 'machines.index', 'permission' => 'manage_parts'],
                ['key' => 'locations', 'label' => 'modules.warehouse_location', 'icon' => 'map-pin', 'route' => 'inventory.locations.index', 'permission' => 'manage_inventory'],
            ],
        ],

        'marketing' => [
            'label' => 'modules.marketing',
            'icon' => 'megaphone',
            'color' => 'violet',
            'items' => [
                ['key' => 'customer-parts', 'label' => 'modules.customer_product_mapping', 'icon' => 'link', 'route' => 'planning.customer-parts.index', 'permission' => 'manage_planning'],
                ['key' => 'boms', 'label' => 'modules.bom', 'icon' => 'tree', 'route' => 'planning.boms.index', 'permission' => 'manage_planning'],
                ['key' => 'pricing', 'label' => 'modules.price_master', 'icon' => 'tag', 'route' => 'pricing.index', 'permission' => 'manage_parts'],
                ['key' => 'customer-po', 'label' => 'modules.customer_po', 'icon' => 'clipboard-doc', 'route' => 'outgoing.customer-po.index', 'permission' => 'manage_outgoing'],
            ],
        ],

        'planning' => [
            'label' => 'modules.planning',
            'icon' => 'calendar',
            'color' => 'sky',
            'items' => [
                ['key' => 'forecasts', 'label' => 'modules.forecast', 'icon' => 'chart-bar', 'route' => 'planning.forecasts.index', 'permission' => 'manage_planning'],
                ['key' => 'daily-demand', 'label' => 'modules.daily_demand', 'icon' => 'calendar-day', 'route' => 'planning.daily-demand.index', 'permission' => 'view_planning'],
                ['key' => 'production-plan', 'label' => 'modules.production_plan', 'icon' => 'list-check', 'route' => 'production.planning.index', 'permission' => 'view_production'],
            ],
        ],

        'purchasing' => [
            'label' => 'modules.purchasing',
            'icon' => 'cart',
            'color' => 'emerald',
            'items' => [
                ['key' => 'mrp', 'label' => 'modules.mrp', 'icon' => 'cpu', 'route' => 'planning.mrp.index', 'permission' => 'manage_planning'],
                ['key' => 'purchase-orders', 'label' => 'modules.purchase_order', 'icon' => 'clipboard-doc', 'route' => 'purchasing.purchase-orders.index', 'permission' => 'manage_purchasing'],
                ['key' => 'material-price', 'label' => 'modules.material_price', 'icon' => 'currency', 'route' => 'purchasing.material-prices.index', 'permission' => 'manage_purchasing'],
                ['key' => 'in-transit-import', 'label' => 'modules.in_transit_import', 'icon' => 'ship', 'route' => 'departures.index', 'permission' => 'manage_incoming'],
            ],
        ],

        'warehouse' => [
            'label' => 'modules.warehouse',
            'icon' => 'warehouse',
            'color' => 'amber',
            'items' => [
                ['key' => 'incoming', 'label' => 'modules.incoming_material', 'icon' => 'arrow-down-tray', 'route' => 'incoming-material.dashboard', 'permission' => 'view_incoming'],
                ['key' => 'outgoing', 'label' => 'modules.outgoing_material', 'icon' => 'arrow-up-tray', 'route' => 'outgoing.delivery-orders.index', 'permission' => 'manage_outgoing'],
                ['key' => 'stock', 'label' => 'modules.stock', 'icon' => 'squares', 'route' => 'stock-card.index', 'permission' => 'manage_inventory'],
                ['key' => 'stock-location', 'label' => 'modules.stock_by_location', 'icon' => 'map-pin', 'route' => 'warehouse.stock.index', 'permission' => 'manage_inventory'],
                ['key' => 'transfer', 'label' => 'modules.transfer_stock', 'icon' => 'arrows', 'route' => 'warehouse.bin-transfers.index', 'permission' => 'manage_inventory'],
                ['key' => 'opname', 'label' => 'modules.stock_opname', 'icon' => 'clipboard-check', 'route' => 'warehouse.stock-opname.index', 'permission' => 'manage_inventory'],
            ],
        ],

        'production' => [
            'label' => 'modules.production',
            'icon' => 'cog',
            'color' => 'orange',
            'items' => [
                ['key' => 'daily-prod-plan', 'label' => 'modules.daily_prod_plan', 'icon' => 'calendar-day', 'route' => 'production.planning.index', 'permission' => 'view_production'],
                ['key' => 'prod-result', 'label' => 'modules.prod_result_input', 'icon' => 'play-pause', 'route' => 'production.mass-production.index', 'permission' => 'view_production'],
                ['key' => 'cycle-time', 'label' => 'modules.cycle_time', 'icon' => 'clock', 'route' => 'production.cycle-time.index', 'permission' => 'view_production'],
            ],
        ],

        'outside-process' => [
            'label' => 'modules.outside_process',
            'icon' => 'globe-arrow',
            'color' => 'teal',
            'items' => [
                ['key' => 'subcon-in', 'label' => 'modules.subcon_in', 'icon' => 'inbox-down', 'route' => 'subcon.receive-index', 'permission' => 'manage_subcon'],
                ['key' => 'subcon-out', 'label' => 'modules.subcon_out', 'icon' => 'outbox-up', 'route' => 'subcon.index', 'permission' => 'manage_subcon'],
                ['key' => 'subcon-request', 'label' => 'modules.request_subcon', 'icon' => 'hand', 'route' => 'subcon.create', 'permission' => 'manage_subcon'],
            ],
        ],

        'admin' => [
            'label' => 'modules.admin',
            'icon' => 'shield',
            'color' => 'rose',
            'items' => [
                ['key' => 'users', 'label' => 'modules.user_management', 'icon' => 'user-cog', 'route' => 'admin.users.index', 'permission' => 'manage_users'],
                ['key' => 'roles', 'label' => 'modules.role_management', 'icon' => 'shield-key', 'route' => 'admin.roles.index', 'permission' => 'manage_users'],
            ],
        ],
    ],
];
