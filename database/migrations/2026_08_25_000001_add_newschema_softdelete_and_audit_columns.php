<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The NewSchema models (App\Models\NewSchema\BaseModel) use SoftDeletes and
 * creator()/updater() relations, but no migration ever created the
 * deleted_at / created_by / updated_by columns. Add them where missing.
 */
return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'customer_parts', 'customers', 'departments', 'drivers', 'gci_parts',
            'incoming_arrival_container_inspections', 'incoming_arrival_containers',
            'incoming_arrival_inspection_issues', 'incoming_arrival_inspections',
            'incoming_arrival_items', 'incoming_arrivals', 'incoming_receives',
            'inventory_bin_transfers', 'inventory_fg_stock', 'inventory_location_stock',
            'inventory_returns', 'inventory_stock_at_customers', 'inventory_stock_movements',
            'inventory_stock_opname_items', 'inventory_stock_opname_sessions',
            'inventory_supplies', 'machines',
            'outgoing_daily_plan_cells', 'outgoing_daily_plan_rows', 'outgoing_daily_plans',
            'outgoing_delivery_note_items', 'outgoing_delivery_notes',
            'outgoing_delivery_order_items', 'outgoing_delivery_orders',
            'outgoing_delivery_planning_lines', 'outgoing_delivery_requirement_fulfillments',
            'outgoing_delivery_requirements', 'outgoing_jig_plans', 'outgoing_jig_settings',
            'outgoing_picking_fg', 'outgoing_po_items', 'outgoing_pos',
            'production_downtimes', 'production_hourly_reports', 'production_inspections',
            'production_material_lots', 'production_order_activities', 'production_order_arrivals',
            'production_order_material_issue_items', 'production_order_material_issues',
            'production_order_material_request_items', 'production_order_material_requests',
            'production_order_reserved_materials', 'production_orders', 'production_work_orders',
            'role_permissions', 'roles', 'sales_order_items', 'sales_orders',
            'trucking_companies', 'trucks', 'uoms', 'users', 'vendor_parts', 'vendors',
            'warehouse_locations',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                if (! Schema::hasColumn($table->getTable(), 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('updated_at');
                }
                if (! Schema::hasColumn($table->getTable(), 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->after('created_by');
                }
                if (! Schema::hasColumn($table->getTable(), 'deleted_at')) {
                    $table->softDeletes()->after('updated_by');
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: dropping columns would destroy audit data.
        DB::statement('SELECT 1');
    }
};
