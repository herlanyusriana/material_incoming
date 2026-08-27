-- ============================================================
-- DATA MIGRATION (OUTGOING): incoming_material_restore -> erp_gci_new
-- Run AFTER migrate_data_from_restore.sql (needs customers/gci_parts/users).
-- SKIPPED (no clean source / redesign): sales_orders, sales_order_items,
--   outgoing_delivery_planning_lines (wide trip_1..14 format),
--   outgoing_jig_settings/plans (source lacks gci_part_id, new col NOT NULL),
--   outgoing_delivery_requirements/fulfillments.
-- FKs to skipped/empty tables are set NULL. NOT NULL FKs guarded by WHERE EXISTS.
-- ============================================================

-- ── TRUCKING COMPANIES ──  (generate company_code from id)
INSERT INTO erp_gci_new.trucking_companies
    (id, company_code, company_name, address, phone, email, contact_person, status, created_at, updated_at)
SELECT id, CONCAT('TC', LPAD(id, 4, '0')), company_name, address, phone, email,
       contact_person, COALESCE(status, 'active'), created_at, updated_at
FROM incoming_material_restore.trucking_companies;

-- ── TRUCKS ──  (plate_no->license_plate, type->truck_type; no company in source)
INSERT INTO erp_gci_new.trucks
    (id, license_plate, truck_type, capacity, trucking_company_id, status, created_at, updated_at)
SELECT id, plate_no, type, capacity, NULL, COALESCE(status, 'active'), created_at, updated_at
FROM incoming_material_restore.trucks;

-- ── DRIVERS ──  (generate driver_code; license_type->license_number)
INSERT INTO erp_gci_new.drivers
    (id, driver_code, driver_name, license_number, phone, trucking_company_id, status, created_at, updated_at)
SELECT id, CONCAT('DRV', LPAD(id, 4, '0')), name, license_type, phone, NULL,
       COALESCE(status, 'active'), created_at, updated_at
FROM incoming_material_restore.drivers;

-- ── OUTGOING DAILY PLANS ──
INSERT INTO erp_gci_new.outgoing_daily_plans
    (id, date_from, date_to, status, created_by, created_at, updated_at)
SELECT p.id, p.date_from, p.date_to, 'draft',
       (SELECT u.id FROM erp_gci_new.users u WHERE u.id = p.created_by),
       p.created_at, p.updated_at
FROM incoming_material_restore.outgoing_daily_plans p;

-- ── OUTGOING DAILY PLAN ROWS ──  (usage_qty->total_qty; customer_part_id NULL (skipped);
--    filter 11 rows with NULL gci_part_id — new col is NOT NULL)
INSERT INTO erp_gci_new.outgoing_daily_plan_rows
    (id, plan_id, row_no, gci_part_id, customer_part_id, daily_quantities, total_qty,
     sales_order_id, created_at, updated_at)
SELECT r.id, r.plan_id, r.row_no, r.gci_part_id, NULL, NULL, COALESCE(r.usage_qty, 0),
       NULL, r.created_at, r.updated_at
FROM incoming_material_restore.outgoing_daily_plan_rows r
WHERE r.gci_part_id IS NOT NULL
  AND EXISTS (SELECT 1 FROM erp_gci_new.outgoing_daily_plans p WHERE p.id = r.plan_id)
  AND EXISTS (SELECT 1 FROM erp_gci_new.gci_parts g WHERE g.id = r.gci_part_id);

-- ── OUTGOING DAILY PLAN CELLS ──  (row_id->plan_row_id, plan_date->date; drop seq;
--    only cells whose row survived; production_order_id NULL)
INSERT INTO erp_gci_new.outgoing_daily_plan_cells
    (id, plan_row_id, date, qty, production_order_id, status, created_at, updated_at)
SELECT c.id, c.row_id, c.plan_date, COALESCE(c.qty, 0), NULL, 'pending', c.created_at, c.updated_at
FROM incoming_material_restore.outgoing_daily_plan_cells c
WHERE EXISTS (SELECT 1 FROM erp_gci_new.outgoing_daily_plan_rows r WHERE r.id = c.row_id);

-- ── OUTGOING POS ──  (po_release_date->order_date)
INSERT INTO erp_gci_new.outgoing_pos
    (id, po_no, customer_id, order_date, delivery_date, status, notes, created_by, created_at, updated_at)
SELECT o.id, o.po_no, o.customer_id, o.po_release_date, NULL, COALESCE(o.status, 'draft'),
       o.notes, (SELECT u.id FROM erp_gci_new.users u WHERE u.id = o.created_by),
       o.created_at, o.updated_at
FROM incoming_material_restore.outgoing_pos o
WHERE EXISTS (SELECT 1 FROM erp_gci_new.customers c WHERE c.id = o.customer_id);

-- ── OUTGOING PO ITEMS ──  (qty->qty_ordered, fulfilled_qty->qty_delivered, price->unit_price)
INSERT INTO erp_gci_new.outgoing_po_items
    (id, outgoing_po_id, gci_part_id, qty_ordered, qty_delivered, unit, unit_price, notes, created_at, updated_at)
SELECT i.id, i.outgoing_po_id, i.gci_part_id, COALESCE(i.qty, 0), COALESCE(i.fulfilled_qty, 0),
       NULL, i.price, i.notes, i.created_at, i.updated_at
FROM incoming_material_restore.outgoing_po_items i
WHERE EXISTS (SELECT 1 FROM erp_gci_new.outgoing_pos p WHERE p.id = i.outgoing_po_id)
  AND EXISTS (SELECT 1 FROM erp_gci_new.gci_parts g WHERE g.id = i.gci_part_id);

-- ── OUTGOING DELIVERY ORDERS ──  (do_date->order_date; delivery_note_id NULL)
INSERT INTO erp_gci_new.outgoing_delivery_orders
    (id, do_no, delivery_note_id, customer_id, order_date, delivery_date, status, notes, created_by, created_at, updated_at)
SELECT d.id, d.do_no, NULL, d.customer_id, d.do_date, NULL, COALESCE(d.status, 'draft'),
       d.notes, (SELECT u.id FROM erp_gci_new.users u WHERE u.id = d.created_by),
       d.created_at, d.updated_at
FROM incoming_material_restore.delivery_orders d
WHERE EXISTS (SELECT 1 FROM erp_gci_new.customers c WHERE c.id = d.customer_id);

-- ── OUTGOING DELIVERY ORDER ITEMS ──  (qty_shipped->qty_delivered)
INSERT INTO erp_gci_new.outgoing_delivery_order_items
    (id, delivery_order_id, gci_part_id, qty_ordered, qty_delivered, unit, unit_price, notes, created_at, updated_at)
SELECT i.id, i.delivery_order_id, i.gci_part_id, COALESCE(i.qty_ordered, 0), COALESCE(i.qty_shipped, 0),
       NULL, NULL, NULL, i.created_at, i.updated_at
FROM incoming_material_restore.delivery_order_items i
WHERE EXISTS (SELECT 1 FROM erp_gci_new.outgoing_delivery_orders o WHERE o.id = i.delivery_order_id)
  AND EXISTS (SELECT 1 FROM erp_gci_new.gci_parts g WHERE g.id = i.gci_part_id);

-- ── OUTGOING DELIVERY NOTES ──  (drop delivery_order_id/delivery_stop/plan; map driver/truck)
INSERT INTO erp_gci_new.outgoing_delivery_notes
    (id, dn_no, transaction_no, customer_id, delivery_date, planned_delivery_date,
     driver_id, truck_id, status, notes, created_at, updated_at)
SELECT n.id, n.dn_no, n.transaction_no, n.customer_id, n.delivery_date, NULL,
       (SELECT dr.id FROM erp_gci_new.drivers dr WHERE dr.id = n.driver_id),
       (SELECT tk.id FROM erp_gci_new.trucks tk WHERE tk.id = n.truck_id),
       COALESCE(n.status, 'draft'), n.notes, n.created_at, n.updated_at
FROM incoming_material_restore.delivery_notes n
WHERE EXISTS (SELECT 1 FROM erp_gci_new.customers c WHERE c.id = n.customer_id);

-- ── OUTGOING DELIVERY NOTE ITEMS ──  (dn_id->delivery_note_id, qty->qty_delivered,
--    kitting_location_code->from_location_code; picking_fg_id NULL)
INSERT INTO erp_gci_new.outgoing_delivery_note_items
    (id, delivery_note_id, gci_part_id, qty_delivered, unit, sales_order_item_id, picking_fg_id,
     batch_no, from_location_code, unit_price, total_price, notes, created_at, updated_at)
SELECT i.id, i.dn_id, i.gci_part_id, COALESCE(i.qty, 0), NULL, NULL, NULL,
       NULL, i.kitting_location_code, NULL, NULL, i.remarks, i.created_at, i.updated_at
FROM incoming_material_restore.dn_items i
WHERE EXISTS (SELECT 1 FROM erp_gci_new.outgoing_delivery_notes n WHERE n.id = i.dn_id)
  AND EXISTS (SELECT 1 FROM erp_gci_new.gci_parts g WHERE g.id = i.gci_part_id);

-- ── OUTGOING PICKING FG ──  (generate picking_no; pick_location->from_location_code;
--    delivery_planning_line_id/sales_order_id NULL (skipped source lines))
INSERT INTO erp_gci_new.outgoing_picking_fg
    (id, picking_no, delivery_planning_line_id, sales_order_id, gci_part_id, qty_picked, unit,
     from_location_code, batch_no, status, picked_by, picked_at, notes, created_at, updated_at)
SELECT f.id, CONCAT('PCK-', LPAD(f.id, 6, '0')), NULL, NULL, f.gci_part_id,
       COALESCE(f.qty_picked, f.qty_plan, 0), NULL, COALESCE(f.pick_location, '-'),
       NULL, COALESCE(f.status, 'draft'),
       (SELECT u.id FROM erp_gci_new.users u WHERE u.id = f.picked_by),
       f.picked_at, f.notes, f.created_at, f.updated_at
FROM incoming_material_restore.outgoing_picking_fgs f
WHERE EXISTS (SELECT 1 FROM erp_gci_new.gci_parts g WHERE g.id = f.gci_part_id);
