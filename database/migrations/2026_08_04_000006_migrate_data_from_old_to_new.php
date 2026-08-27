<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Copies data from the OLD schema database into the NEW schema tables.
 *
 * The new schema lives in a separate database. This migration reads from the
 * source database (configurable via env MIGRATE_SOURCE_DATABASE, defaults to
 * the old `erp_development` DB) and inserts into the current connection,
 * preserving IDs so foreign keys stay intact.
 *
 * Only the priority domains are migrated: Core, Incoming, Production,
 * Inventory, Outgoing. Planning is intentionally skipped.
 */
return new class extends Migration {
    protected string $src;

    public function up(): void
    {
        $this->src = (string) env('MIGRATE_SOURCE_DATABASE', 'erp_development');

        if (! $this->srcTableExists('gci_parts')) {
            return; // Nothing to migrate — skip silently on a fresh install
        }

        $this->copyUsers();
        $this->copyVendors();
        $this->copyCustomers();
        $this->copyGciParts();
        $this->copyVendorParts();
        $this->copyWarehouseLocations();
        $this->copyCustomerParts();
        $this->copyIncomingArrivals();
        $this->copyIncomingArrivalItems();
        $this->copyIncomingArrivalContainers();
        $this->copyIncomingReceives();
        $this->copyProductionOrders();
        $this->copyProductionOrderActivities();
        $this->copyOutgoingSalesOrders();
    }

    public function down(): void
    {
        // Data migration — no-op on rollback (new DB can be recreated).
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    protected function srcTableExists(string $table): bool
    {
        return DB::connection('mysql')->getSchemaBuilder()->hasTable("{$this->src}.{$table}");
    }

    protected function srcRows(string $table): array
    {
        return DB::connection('mysql')
            ->table("{$this->src}.{$table}")
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    protected function insertBatch(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    // ──────────────────────────────────────────────
    // Copy routines
    // ──────────────────────────────────────────────

    protected function copyUsers(): void
    {
        if (! $this->srcTableExists('users') || DB::table('users')->exists()) {
            return;
        }
        $rows = $this->srcRows('users');
        if (empty($rows)) {
            return;
        }
        // Avoid copying the default Laravel user if already present
        $this->insertBatch('users', $rows);
    }

    protected function copyVendors(): void
    {
        if (! $this->srcTableExists('vendors') || DB::table('vendors')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'vendor_code' => $r['vendor_code'] ?? null,
            'vendor_name' => $r['vendor_name'] ?? '',
            'vendor_type' => $r['vendor_type'] ?? null,
            'country_code' => $r['country_code'] ?? null,
            'address' => $r['address'] ?? null,
            'bank_account' => $r['bank_account'] ?? null,
            'contact_person' => $r['contact_person'] ?? null,
            'email' => $r['email'] ?? null,
            'phone' => $r['phone'] ?? null,
            'signature_path' => $r['signature_path'] ?? null,
            'status' => $r['status'] ?? 'active',
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows('vendors'));

        $this->insertBatch('vendors', $rows);
    }

    protected function copyCustomers(): void
    {
        if (! $this->srcTableExists('customers') || DB::table('customers')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'code' => $r['code'] ?? $r['customer_code'] ?? '',
            'name' => $r['name'] ?? $r['customer_name'] ?? '',
            'status' => $r['status'] ?? 'active',
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows('customers'));

        $this->insertBatch('customers', $rows);
    }

    protected function copyGciParts(): void
    {
        if (! $this->srcTableExists('gci_parts') || DB::table('gci_parts')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'part_no' => $r['part_no'],
            'barcode' => $r['barcode'] ?? $r['part_no'] ?? null,
            'part_name' => $r['part_name'] ?? '',
            'size' => $r['size'] ?? null,
            'model' => $r['model'] ?? null,
            'classification' => $r['classification'] ?? null,
            'status' => $r['status'] ?? 'active',
            'net_weight' => $r['net_weight'] ?? null,
            'gross_weight' => $r['gross_weight'] ?? null,
            'is_backflush' => $r['is_backflush'] ?? false,
            'consumption_policy' => $r['consumption_policy'] ?? null,
            'subcount_enabled' => $r['subcount_enabled'] ?? false,
            'subcount_uom' => $r['subcount_uom'] ?? null,
            'subcount_process_type' => $r['subcount_process_type'] ?? null,
            'default_location' => $r['default_location'] ?? null,
            'policy_confirmed_at' => $r['policy_confirmed_at'] ?? null,
            'policy_confirmed_by' => $r['policy_confirmed_by'] ?? null,
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows('gci_parts'));

        $this->insertBatch('gci_parts', $rows);
    }

    protected function copyVendorParts(): void
    {
        $source = $this->srcTableExists('vendor_parts') ? 'vendor_parts' : 'gci_part_vendor';
        if (! $this->srcTableExists($source) || DB::table('vendor_parts')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'gci_part_id' => $r['gci_part_id'],
            'vendor_id' => $r['vendor_id'],
            'vendor_part_no' => $r['vendor_part_no'] ?? null,
            'vendor_part_name' => $r['vendor_part_name'] ?? null,
            'register_no' => $r['register_no'] ?? null,
            'price' => $r['price'] ?? null,
            'currency' => $r['currency'] ?? null,
            'uom' => $r['uom'] ?? null,
            'quality_inspection' => $r['quality_inspection'] ?? false,
            'status' => $r['status'] ?? 'active',
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows($source));

        $this->insertBatch('vendor_parts', $rows);
    }

    protected function copyWarehouseLocations(): void
    {
        if (! $this->srcTableExists('warehouse_locations') || DB::table('warehouse_locations')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'location_code' => $r['location_code'],
            'location_name' => $r['location_name'] ?? null,
            'zone' => $r['zone'] ?? null,
            'rack' => $r['rack'] ?? null,
            'shelf' => $r['shelf'] ?? null,
            'bin' => $r['bin'] ?? null,
            'location_type' => $r['location_type'] ?? null,
            'is_active' => $r['is_active'] ?? true,
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows('warehouse_locations'));

        $this->insertBatch('warehouse_locations', $rows);
    }

    protected function copyCustomerParts(): void
    {
        if (! $this->srcTableExists('customer_parts') || DB::table('customer_parts')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'customer_id' => $r['customer_id'],
            'part_no' => $r['part_no'],
            'part_name' => $r['part_name'] ?? null,
            'description' => $r['description'] ?? null,
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows('customer_parts'));

        $this->insertBatch('customer_parts', $rows);
    }

    protected function copyIncomingArrivals(): void
    {
        if (! $this->srcTableExists('arrivals') || DB::table('incoming_arrivals')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'arrival_no' => $r['arrival_no'],
            'transaction_no' => $r['transaction_no'] ?? null,
            'invoice_no' => $r['invoice_no'] ?? null,
            'invoice_date' => $r['invoice_date'] ?? null,
            'vendor_id' => $r['vendor_id'] ?? null,
            'trucking_company_id' => $r['trucking_company_id'] ?? null,
            'created_by' => $r['created_by'] ?? null,
            'vessel' => $r['vessel'] ?? null,
            'etd' => $r['ETD'] ?? $r['etd'] ?? null,
            'eta' => $r['ETA'] ?? $r['eta'] ?? null,
            'eta_gci' => $r['ETA_GCI'] ?? $r['eta_gci'] ?? null,
            'bill_of_lading' => $r['bill_of_lading'] ?? null,
            'pen_no' => $r['pen_no'] ?? null,
            'pen_date' => $r['pen_date'] ?? null,
            'aju_no' => $r['aju_no'] ?? null,
            'bill_of_lading_file' => $r['bill_of_lading_file'] ?? null,
            'delivery_note_file' => $r['delivery_note_file'] ?? null,
            'invoice_file' => $r['invoice_file'] ?? null,
            'packing_list_file' => $r['packing_list_file'] ?? null,
            'price_term' => $r['price_term'] ?? null,
            'hs_code' => $r['hs_code'] ?? null,
            'port_of_loading' => $r['port_of_loading'] ?? null,
            'country' => $r['country'] ?? null,
            'currency' => $r['currency'] ?? null,
            'notes' => $r['notes'] ?? null,
            'status' => $r['status'] ?? 'pending',
            'purchase_order_id' => $r['purchase_order_id'] ?? null,
            'deleted_at' => $r['deleted_at'] ?? null,
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows('arrivals'));

        $this->insertBatch('incoming_arrivals', $rows);
    }

    protected function copyIncomingArrivalItems(): void
    {
        if (! $this->srcTableExists('arrival_items') || DB::table('incoming_arrival_items')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'arrival_id' => $r['arrival_id'],
            'gci_part_id' => $r['gci_part_id'] ?? null,
            'material_group' => $r['material_group'] ?? null,
            'size' => $r['size'] ?? null,
            'qty_goods' => $r['qty_goods'] ?? 0,
            'unit_goods' => $r['unit_goods'] ?? null,
            'qty_bundle' => $r['qty_bundle'] ?? null,
            'unit_bundle' => $r['unit_bundle'] ?? null,
            'weight_nett' => $r['weight_nett'] ?? null,
            'unit_weight' => $r['unit_weight'] ?? null,
            'weight_gross' => $r['weight_gross'] ?? null,
            'price' => $r['price'] ?? $r['price_unit'] ?? null,
            'total_price' => $r['total_price'] ?? null,
            'notes' => $r['notes'] ?? null,
            'is_foc' => $r['is_foc'] ?? false,
            'deleted_at' => $r['deleted_at'] ?? null,
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows('arrival_items'));

        $this->insertBatch('incoming_arrival_items', $rows);
    }

    protected function copyIncomingArrivalContainers(): void
    {
        if (! $this->srcTableExists('arrival_containers') || DB::table('incoming_arrival_containers')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'arrival_id' => $r['arrival_id'],
            'container_no' => $r['container_no'],
            'seal_code' => $r['seal_code'] ?? $r['seal_no'] ?? null,
            'size' => $r['size'] ?? null,
            'deleted_at' => $r['deleted_at'] ?? null,
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows('arrival_containers'));

        $this->insertBatch('incoming_arrival_containers', $rows);
    }

    protected function copyIncomingReceives(): void
    {
        if (! $this->srcTableExists('receives') || DB::table('incoming_receives')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'arrival_item_id' => $r['arrival_item_id'],
            'gci_part_id' => $r['gci_part_id'] ?? null,
            'tag' => $r['tag'] ?? null,
            'qty' => $r['qty'] ?? 0,
            'qty_unit' => $r['qty_unit'] ?? null,
            'bundle_qty' => $r['bundle_qty'] ?? null,
            'bundle_unit' => $r['bundle_unit'] ?? null,
            'weight' => $r['weight'] ?? null,
            'net_weight' => $r['net_weight'] ?? null,
            'gross_weight' => $r['gross_weight'] ?? null,
            'weight_kgm' => $r['weight_kgm'] ?? null,
            'location_code' => $r['location_code'] ?? null,
            'qc_status' => $r['qc_status'] ?? null,
            'jo_po_number' => $r['jo_po_number'] ?? null,
            'ata_date' => $r['ata_date'] ?? null,
            'qc_audited_at' => $r['qc_audited_at'] ?? null,
            'qc_audited_by' => $r['qc_audited_by'] ?? null,
            'deleted_at' => $r['deleted_at'] ?? null,
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows('receives'));

        $this->insertBatch('incoming_receives', $rows);
    }

    protected function copyProductionOrders(): void
    {
        if (! $this->srcTableExists('production_orders') || DB::table('production_orders')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'production_order_number' => $r['production_order_number'],
            'transaction_no' => $r['transaction_no'] ?? null,
            'gci_part_id' => $r['gci_part_id'],
            'plan_date' => $r['plan_date'],
            'qty_planned' => $r['qty_planned'] ?? 0,
            'qty_actual' => $r['qty_actual'] ?? 0,
            'machine_id' => $r['machine_id'] ?? null,
            'status' => $r['status'] ?? 'planned',
            'workflow_stage' => $r['workflow_stage'] ?? null,
            'start_time' => $r['start_time'] ?? null,
            'end_time' => $r['end_time'] ?? null,
            'material_requested_at' => $r['material_requested_at'] ?? null,
            'material_issued_at' => $r['material_issued_at'] ?? null,
            'material_handed_over_at' => $r['material_handed_over_at'] ?? null,
            'fg_supplied_to_wh_at' => $r['fg_supplied_to_wh_at'] ?? null,
            'fg_handed_over_to_wh_at' => $r['fg_handed_over_to_wh_at'] ?? null,
            'last_handover_at' => $r['last_handover_at'] ?? null,
            'created_by' => $r['created_by'] ?? null,
            'material_requested_by' => $r['material_requested_by'] ?? null,
            'material_issued_by' => $r['material_issued_by'] ?? null,
            'material_handed_over_by' => $r['material_handed_over_by'] ?? null,
            'fg_supplied_to_wh_by' => $r['fg_supplied_to_wh_by'] ?? null,
            'fg_handed_over_to_wh_by' => $r['fg_handed_over_to_wh_by'] ?? null,
            'is_kanban_released' => $r['is_kanban_released'] ?? false,
            'kanban_released_at' => $r['kanban_released_at'] ?? null,
            'active_operator_started_at' => $r['active_operator_started_at'] ?? null,
            'active_operator_username' => $r['active_operator_username'] ?? null,
            'mps_id' => $r['mps_id'] ?? null,
            'mrp_run_id' => $r['mrp_run_id'] ?? null,
            'planning_line_id' => $r['planning_line_id'] ?? null,
            'daily_plan_cell_id' => $r['daily_plan_cell_id'] ?? null,
            'deleted_at' => $r['deleted_at'] ?? null,
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows('production_orders'));

        $this->insertBatch('production_orders', $rows);
    }

    protected function copyProductionOrderActivities(): void
    {
        if (! $this->srcTableExists('production_order_activities') || DB::table('production_order_activities')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'production_order_id' => $r['production_order_id'],
            'action' => $r['action'],
            'description' => $r['description'] ?? null,
            'metadata' => $r['metadata'] ?? null,
            'performed_by' => $r['performed_by'] ?? null,
            'deleted_at' => $r['deleted_at'] ?? null,
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows('production_order_activities'));

        $this->insertBatch('production_order_activities', $rows);
    }

    protected function copyOutgoingSalesOrders(): void
    {
        $source = $this->srcTableExists('sales_orders') ? 'sales_orders' : null;
        if (! $source || DB::table('sales_orders')->exists()) {
            return;
        }
        $rows = array_map(fn ($r) => [
            'id' => $r['id'],
            'so_no' => $r['so_no'],
            'customer_id' => $r['customer_id'],
            'order_date' => $r['order_date'],
            'delivery_date' => $r['delivery_date'] ?? null,
            'po_customer_no' => $r['po_customer_no'] ?? null,
            'status' => $r['status'] ?? 'draft',
            'notes' => $r['notes'] ?? null,
            'created_by' => $r['created_by'] ?? null,
            'deleted_at' => $r['deleted_at'] ?? null,
            'created_at' => $r['created_at'] ?? now(),
            'updated_at' => $r['updated_at'] ?? now(),
        ], $this->srcRows($source));

        $this->insertBatch('sales_orders', $rows);
    }
};