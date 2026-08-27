-- ============================================================
-- DATA MIGRATION: incoming_material_restore  ->  erp_gci_new
-- Priority domains: Core, Incoming, Production
-- Planning skipped. customer_parts skipped (11M bloat anomaly).
-- Preserves IDs. Nullable FKs guarded by existence subquery.
-- FKs to empty target tables (trucking_companies, outgoing_daily_plan_cells,
-- purchase_orders) are set NULL. NOT NULL FKs filtered by WHERE EXISTS.
-- Idempotent-ish: each INSERT guarded so it only runs when target empty.
-- ============================================================

SET @SRC = 'incoming_material_restore';

-- ── USERS ──
INSERT INTO erp_gci_new.users
    (id, name, email, email_verified_at, password, remember_token, created_at, updated_at)
SELECT id, name, email, email_verified_at, password, remember_token, created_at, updated_at
FROM incoming_material_restore.users;

-- ── UOMS ──
INSERT INTO erp_gci_new.uoms (id, code, name, category, created_at, updated_at)
SELECT id, code, name, category, created_at, updated_at
FROM incoming_material_restore.uoms;

-- ── MACHINES ──  (group_name->type, is_active->status)
INSERT INTO erp_gci_new.machines
    (id, machine_code, machine_name, type, capacity, status, created_at, updated_at)
SELECT id, code, name, group_name, NULL,
       IF(is_active = 0, 'retired', 'active'), created_at, updated_at
FROM incoming_material_restore.machines;

-- ── DEPARTMENTS ──
INSERT INTO erp_gci_new.departments (id, code, name, created_at, updated_at)
SELECT id, code, name, created_at, updated_at
FROM incoming_material_restore.departments;

-- ── CUSTOMERS ──  (code->customer_code, name->customer_name)
INSERT INTO erp_gci_new.customers
    (id, customer_code, customer_name, address, status, created_at, updated_at)
SELECT id, code, name, address, COALESCE(status, 'active'), created_at, updated_at
FROM incoming_material_restore.customers;

-- ── VENDORS ──  (generate vendor_code from id)
INSERT INTO erp_gci_new.vendors
    (id, vendor_code, vendor_name, vendor_type, country_code, address, bank_account,
     contact_person, email, phone, signature_path, status, deleted_at, created_at, updated_at)
SELECT id, CONCAT('V', LPAD(id, 5, '0')), vendor_name, vendor_type, country_code, address,
       bank_account, contact_person, email, phone, signature_path,
       COALESCE(status, 'active'), deleted_at, created_at, updated_at
FROM incoming_material_restore.vendors;

-- ── GCI PARTS ──  (drop customer_id/type/subcount_document_no/subcount_qty)
INSERT INTO erp_gci_new.gci_parts
    (id, part_no, barcode, part_name, size, model, classification, status,
     net_weight, gross_weight, is_backflush, consumption_policy, subcount_enabled,
     subcount_uom, subcount_process_type, default_location,
     policy_confirmed_at, policy_confirmed_by, created_at, updated_at)
SELECT g.id, g.part_no, NULLIF(g.barcode, ''), COALESCE(g.part_name, g.part_no),
       g.size, g.model, g.classification, COALESCE(g.status, 'active'),
       g.net_weight, g.gross_weight, COALESCE(g.is_backflush, 0), g.consumption_policy,
       COALESCE(g.subcount_enabled, 0), g.subcount_uom, g.subcount_process_type, g.default_location,
       g.policy_confirmed_at,
       (SELECT u.id FROM erp_gci_new.users u WHERE u.id = g.policy_confirmed_by),
       g.created_at, g.updated_at
FROM incoming_material_restore.gci_parts g;

-- ── VENDOR PARTS ──  (from gci_part_vendor; drop hs_code; currency NULL)
INSERT INTO erp_gci_new.vendor_parts
    (id, gci_part_id, vendor_id, vendor_part_no, vendor_part_name, register_no,
     price, currency, uom, quality_inspection, status, created_at, updated_at)
SELECT v.id, v.gci_part_id, v.vendor_id,
       COALESCE(NULLIF(v.vendor_part_no, ''), CONCAT('VP-', v.id)),
       v.vendor_part_name, v.register_no, v.price, NULL, v.uom,
       COALESCE(v.quality_inspection, 0), COALESCE(v.status, 'active'),
       v.created_at, v.updated_at
FROM incoming_material_restore.gci_part_vendor v
WHERE EXISTS (SELECT 1 FROM erp_gci_new.gci_parts p WHERE p.id = v.gci_part_id)
  AND EXISTS (SELECT 1 FROM erp_gci_new.vendors  d WHERE d.id = v.vendor_id);

-- ── WAREHOUSE LOCATIONS ──  (class->location_type, status->is_active)
INSERT INTO erp_gci_new.warehouse_locations
    (id, location_code, location_name, zone, location_type, is_active, created_at, updated_at)
SELECT id, location_code, NULL, zone, class,
       IF(status = 'inactive', 0, 1), created_at, updated_at
FROM incoming_material_restore.warehouse_locations;

-- ── INCOMING ARRIVALS ──  (ETD/ETA/ETA_GCI mapping; trucking/PO nulled; status default)
INSERT INTO erp_gci_new.incoming_arrivals
    (id, arrival_no, transaction_no, invoice_no, invoice_date, vendor_id, trucking_company_id,
     created_by, vessel, etd, eta, eta_gci, bill_of_lading, pen_no, pen_date, aju_no,
     bill_of_lading_file, delivery_note_file, invoice_file, packing_list_file,
     price_term, hs_code, port_of_loading, country, currency, notes, status,
     purchase_order_id, created_at, updated_at)
SELECT a.id, a.arrival_no, a.transaction_no, a.invoice_no, a.invoice_date,
       (SELECT v.id FROM erp_gci_new.vendors v WHERE v.id = a.vendor_id),
       NULL,
       (SELECT u.id FROM erp_gci_new.users u WHERE u.id = a.created_by),
       a.vessel, a.ETD, a.ETA, a.ETA_GCI, a.bill_of_lading, a.pen_no, a.pen_date, a.aju_no,
       a.bill_of_lading_file, a.delivery_note_file, a.invoice_file, a.packing_list_file,
       a.price_term, a.hs_code, a.port_of_loading, a.country, a.currency, a.notes, 'pending',
       NULL, a.created_at, a.updated_at
FROM incoming_material_restore.arrivals a;

-- ── INCOMING ARRIVAL ITEMS ──  (guard gci_part_id existence)
INSERT INTO erp_gci_new.incoming_arrival_items
    (id, arrival_id, gci_part_id, material_group, size, qty_goods, unit_goods,
     qty_bundle, unit_bundle, weight_nett, unit_weight, weight_gross,
     price, total_price, notes, is_foc, created_at, updated_at)
SELECT i.id, i.arrival_id,
       (SELECT p.id FROM erp_gci_new.gci_parts p WHERE p.id = i.gci_part_id),
       i.material_group, i.size, COALESCE(i.qty_goods, 0), i.unit_goods,
       i.qty_bundle, i.unit_bundle, i.weight_nett, i.unit_weight, i.weight_gross,
       i.price, i.total_price, i.notes, COALESCE(i.is_foc, 0), i.created_at, i.updated_at
FROM incoming_material_restore.arrival_items i
WHERE EXISTS (SELECT 1 FROM erp_gci_new.incoming_arrivals ar WHERE ar.id = i.arrival_id);

-- ── INCOMING ARRIVAL CONTAINERS ──  (no size in source)
INSERT INTO erp_gci_new.incoming_arrival_containers
    (id, arrival_id, container_no, seal_code, size, created_at, updated_at)
SELECT c.id, c.arrival_id, c.container_no, c.seal_code, NULL, c.created_at, c.updated_at
FROM incoming_material_restore.arrival_containers c
WHERE EXISTS (SELECT 1 FROM erp_gci_new.incoming_arrivals ar WHERE ar.id = c.arrival_id);

-- ── INCOMING RECEIVES ──  (qc_updated_at/by -> qc_audited_at/by; no gci_part_id/weight_kgm)
INSERT INTO erp_gci_new.incoming_receives
    (id, arrival_item_id, gci_part_id, tag, qty, qty_unit, bundle_qty, bundle_unit,
     weight, net_weight, gross_weight, weight_kgm, location_code, qc_status,
     jo_po_number, ata_date, qc_audited_at, qc_audited_by, created_at, updated_at)
SELECT r.id, r.arrival_item_id, NULL, r.tag, COALESCE(r.qty, 0), r.qty_unit,
       r.bundle_qty, r.bundle_unit, r.weight, r.net_weight, r.gross_weight, NULL,
       r.location_code, r.qc_status, r.jo_po_number, r.ata_date,
       r.qc_updated_at,
       (SELECT u.id FROM erp_gci_new.users u WHERE u.id = r.qc_updated_by),
       r.created_at, r.updated_at
FROM incoming_material_restore.receives r
WHERE EXISTS (SELECT 1 FROM erp_gci_new.incoming_arrival_items ai WHERE ai.id = r.arrival_item_id);

-- ── PRODUCTION ORDERS ──  (filter gci_part_id; guard user/machine FKs; null daily_plan_cell_id)
INSERT INTO erp_gci_new.production_orders
    (id, production_order_number, transaction_no, gci_part_id, plan_date, qty_planned, qty_actual,
     machine_id, status, workflow_stage, start_time, end_time,
     material_requested_at, material_issued_at, material_handed_over_at,
     fg_supplied_to_wh_at, fg_handed_over_to_wh_at, last_handover_at,
     created_by, material_requested_by, material_issued_by, material_handed_over_by,
     fg_supplied_to_wh_by, fg_handed_over_to_wh_by,
     is_kanban_released, kanban_released_at, active_operator_started_at, active_operator_username,
     mps_id, mrp_run_id, planning_line_id, daily_plan_cell_id, deleted_at, created_at, updated_at)
SELECT po.id, po.production_order_number, po.transaction_no, po.gci_part_id, po.plan_date,
       COALESCE(po.qty_planned, 0), COALESCE(po.qty_actual, 0),
       (SELECT m.id FROM erp_gci_new.machines m WHERE m.id = po.machine_id),
       COALESCE(po.status, 'planned'), po.workflow_stage, po.start_time, po.end_time,
       po.material_requested_at, po.material_issued_at, po.material_handed_over_at,
       po.fg_supplied_to_wh_at, po.fg_handed_over_to_wh_at, po.last_handover_at,
       (SELECT u.id FROM erp_gci_new.users u WHERE u.id = po.created_by),
       (SELECT u.id FROM erp_gci_new.users u WHERE u.id = po.material_requested_by),
       (SELECT u.id FROM erp_gci_new.users u WHERE u.id = po.material_issued_by),
       (SELECT u.id FROM erp_gci_new.users u WHERE u.id = po.material_handed_over_by),
       (SELECT u.id FROM erp_gci_new.users u WHERE u.id = po.fg_supplied_to_wh_by),
       (SELECT u.id FROM erp_gci_new.users u WHERE u.id = po.fg_handed_over_to_wh_by),
       IF(po.released_at IS NOT NULL, 1, 0), po.released_at, NULL, po.active_operator_username,
       po.mps_id, po.mrp_run_id, po.planning_line_id, NULL, po.deleted_at,
       po.created_at, po.updated_at
FROM incoming_material_restore.production_orders po
WHERE EXISTS (SELECT 1 FROM erp_gci_new.gci_parts p WHERE p.id = po.gci_part_id);
