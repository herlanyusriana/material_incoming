<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * All NewSchema models extend BaseModel which uses SoftDeletes.
     * Many tables were created before the NewSchema refactoring and are
     * missing the `deleted_at` column, causing:
     *   SQLSTATE[42S22]: Unknown column 'xxx.deleted_at' in 'where clause'
     *
     * This migration adds `deleted_at` + index to every affected table.
     */
    private array $tables = [
        // Core
        'users',
        'customers',
        'gci_parts',
        'customer_parts',
        'customer_part_components',
        'departments',
        'warehouse_locations',
        'uoms',
        'machines',
        'trucking_companies',
        'trucks',
        'drivers',

        // Incoming
        'incoming_arrivals',
        'incoming_arrival_items',
        'incoming_arrival_containers',
        'incoming_arrival_inspections',
        'incoming_arrival_container_inspections',
        'incoming_arrival_inspection_issues',
        'incoming_receives',

        // Production
        'production_work_orders',
        'production_orders',
        'production_order_activities',
        'production_order_arrivals',
        'production_order_material_requests',
        'production_order_material_request_items',
        'production_order_material_issues',
        'production_order_material_issue_items',
        'production_order_reserved_materials',
        'production_consumed_materials',
        'production_material_lots',
        'production_inspections',
        'production_downtimes',
        'production_hourly_reports',

        // Inventory
        'inventory_location_stock',
        'inventory_stock_movements',
        'inventory_supplies',
        'inventory_fg_stock',
        'inventory_stock_at_customers',
        'inventory_stock_opname_sessions',
        'inventory_stock_opname_items',
        'inventory_bin_transfers',
        'inventory_returns',

        // Outgoing
        'sales_orders',
        'sales_order_items',
        'outgoing_pos',
        'outgoing_po_items',
        'outgoing_delivery_orders',
        'outgoing_delivery_order_items',
        'outgoing_delivery_notes',
        'outgoing_delivery_note_items',
        'outgoing_delivery_requirements',
        'outgoing_delivery_requirement_fulfillments',
        'outgoing_delivery_planning_lines',
        'outgoing_picking_fg',
        'outgoing_daily_plans',
        'outgoing_daily_plan_rows',
        'outgoing_daily_plan_cells',
        'outgoing_jig_settings',
        'outgoing_jig_plans',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            if (Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            if (!Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
