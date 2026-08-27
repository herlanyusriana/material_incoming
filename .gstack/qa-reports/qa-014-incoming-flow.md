# QA-014 — Incoming flow blocked (arrival create 500) — HIGH

Date: 2026-08-25
Found via: Full E2E feature test (incoming→inventory) — `tests/Feature/IncomingFlowToInventoryE2ETest.php`

## What breaks
The **incoming → inventory flow is blocked at the FIRST step.** Creating a
departure / incoming (`ArrivalController::store`, POST /departures) with an item
throws a 500 because the SQL insert references columns that do not exist on the
`incoming_arrival_items` table (built with the NEW schema, prod & test):

```
insert into incoming_arrival_items (part_id, gci_part_vendor_id, gci_part_id, ...)
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'part_id' in 'field list'
```

## Root cause (schema / code mismatch, in the NewSchema refactor)
`ArrivalController.php` writes legacy column names, but the NewSchema
`incoming_arrival_items` table only has `vendor_part_id`:

| Written by code   | Actually exists on `incoming_arrival_items` |
|-------------------|--------------------------------------------|
| `part_id`         | ❌ (column is `vendor_part_id`)             |
| `gci_part_vendor_id` | ❌                                      |
| `incoming_arrival_id` | ❌ (the FK column is `arrival_id`)      |
| `vendor_part_id`  | ✅ not written                             |

Confirmed against **production** `erp_gci_new` (`SHOW COLUMNS incoming_arrival_items`):
columns = `id, arrival_id, gci_part_id, vendor_part_id, material_group, size,
qty_goods, unit_goods, qty_bundle, unit_bundle, weight_nett, unit_weight,
weight_gross, price, total_price, notes, is_foc, deleted_at, created_at,
updated_at, created_by, updated_by` → **no `part_id`**, no `gci_part_vendor_id`,
no `incoming_arrival_id`. The migration
`2026_08_04_000002_create_incoming_tables.php` only adds `vendor_part_id` (later,
via `2026_08_20_020000_add_receive_and_item_reference_columns.php`).

Because the arrival item never persists, the downstream steps (receive, putaway,
inventory stock via `InventoryLocationStock::updateStock`) can never run → the
whole "incoming → inventory" flow is dead.

## Additional read-site risk
`ArrivalController` also READS `->part_id` on arrival items (lines 475, 524, 835,
923) e.g. `adjustIncomingOnOrder((int) $arrivalItem->part_id, ...)`. With no
`part_id` column those will read as `null` (silent data loss). Same class of bug.

## Recommended fix (within the ArrivalController refactor)
1. In `ArrivalController::store` item create: replace `'part_id'` /
   `'gci_part_vendor_id'` with `'vendor_part_id' => $item['part_id']`.
2. Update the 4 `->part_id` read sites → `->vendor_part_id`.
3. Verify `items()` relation FK on `IncomingArrival` maps to `arrival_id`
   (not `incoming_arrival_id`) — the current insert uses the latter.
4. `RepairPartRelations` command references `part_id`/`gci_part_vendor_id` on the
   LEGACY `arrival_items` table — confirm it is not pointed at the NEW table.

## Status — ✅ FIXED (commit `5cb7337`)
- **FIXED** on 2026-08-25 (user requested the fix even though the file is WIP
  refactor — Q-014 exempted from the "don't bundle WIP" rule).
- **Regression test now GREEN**: `tests/Feature/IncomingFlowToInventoryE2ETest.php`
  passes (2 tests, 16 assertions) — full **incoming → inventory** chain works.
- **Full suite green**: 67 passed / 3 skipped / 238 assertions (no regressions;
  the temporary mass-failures were the known stale test-DB Q-002, resolved with
  `reset-test` → `erp_gci_test`).

## What was actually fixed (3-layer root cause)
The defect was **systemic**, not one column:

1. **Controller wrote phantom columns** — `ArrivalController::store` / `storeItem`
   created items with `part_id` + `gci_part_vendor_id` (never existed on the
   NewSchema table). **→ changed to write `vendor_part_id`** (2 create-sites) +
   fixed the 4 `->part_id` read-sites to `->vendor_part_id`.
2. **`IncomingArrival::items()` relation** was `hasMany(IncomingArrivalItem::class)`
   with **no explicit FK** → Laravel guessed `incoming_arrival_id` (doesn't exist).
   **→ added `'arrival_id'`.**
3. **`IncomingArrivalItem::receives()` relation** was `hasMany(IncomingReceive::class)`
   with **no explicit FK** → Laravel guessed `incoming_arrival_item_id` (doesn't
   exist; actual column is `arrival_item_id`). **→ added `'arrival_item_id'`.**
   Also removed the phantom `part_id` / `gci_part_vendor_id` from
   `IncomingArrivalItem::$fillable` (they are not columns).

## Follow-up worth noting (same class, NOT part of this fix)
The Incoming NewSchema models still have other parent→child relations missing
explicit FKs: `IncomingArrival::containers()` (→ should be `arrival_id`),
`inspection()`/`inspections()` (→ `arrival_id`), `IncomingArrivalContainer::inspection()`
(→ `arrival_container_id`), `IncomingArrivalInspection::issues()` (→ `inspection_id`),
and `IncomingArrival::receives()` (semantically wrong — receives belong to items).
These can write/read the wrong FK and affect the container/inspection detail flows;
recommend the same explicit-FK fix there.
