# Alur Kerja Planning: Forecast → MRP → PO → Actualize

Dokumentasi end-to-end alur planning material di ERP ini, dari forecast bulanan sampai
PO vendor ditutup. Semua angka berjalan lewat **MRP engine** di
`App\Http\Controllers\Planning\MrpController`.

> Format tanggal/minggu yang dipakai:
> - `YYYY-MM` → bulan (forecast), contoh `2026-09`
> - `YYYY-Www` → ISO week (MRP run, ETA week, order week), contoh `2026-W36`

---

## Peta Status (dua rangkaian, jangan tertukar)

Ada **dua lifecycle status** yang berdiri sendiri:

| Layer | Status | Aksi | Gate (permission) |
|---|---|---|---|
| **MRP plan** | `pending → approved / rejected` | Approve / reject plan | `approve_mrp` |
| **PO vendor** | `Pending → Approved → Released → Closed / Partially Received` | Generate, approve, release, actualize | `release_po` (release) |

> ⚠️ **MRP approve ≠ PO approve.** Setelah PO dibuat, barang "pindah" dari MRP ke menu
> **Purchasing** untuk difinalkan. `mrp_purchase_plans.status` hanya mengatur rencana
> beli; `purchase_orders.status` mengatur dokumen vendor.

---

## Diagram End-to-End

```
Forecast (bulanan)
     │  period = YYYY-MM, demand = qty
     ▼
┌─ 1. GENERATE MRP ─────────────────────────────────────────────┐
│  planning/mrp/generate  (per ISO week)                        │
│                                                               │
│  Demand bulanan diprorata ke workday di tiap minggu ISO        │
│  → $weeklyPlannedQtyByPart                                    │
│                                                               │
│  Part dikelompokkan:                                          │
│   • Top-level punya BOM aktif → PRODUCTION (MAKE)             │
│   • Top-level TIDAK punya BOM → PURCHASE (BUY)  [drop-ship]   │
│   • Komponen BOM → ikut make_or_buy per item                  │
└───────────────────────────────────────────────────────────────┘
     │
     ▼
┌─ 2. HITUNG NET REQUIREMENT ───────────────────────────────────┐
│  Per part per tanggal:                                        │
│  net = max(0, demand + safety_stock                          │
│           − (on_hand + incoming) − on_order)                 │
│                                                               │
│  on_order  = PO Pending/Approved − qty_received  [komit]      │
│  incoming  = arrival(invoice_date) + receive(ata_date)        │
│              (tidak dobel hitung dengan on_order)             │
│                                                               │
│  Lalu MOQ + order_multiple round-up:                          │
│   - kalau net < vendor.min_order_qty → naikkan ke min_order_qty
│   - kalau order_multiple > 0      → ceil ke kelipatan          │
└───────────────────────────────────────────────────────────────┘
     │
     ▼
┌─ 3. JADIIN ROW PLAN ──────────────────────────────────────────┐
│  mrp_purchase_plans  (BUY) → status = pending                 │
│     part_id, plan_date, eta_week, order_week,                 │
│     required_qty, on_hand, on_order, incoming_stock,          │
│     net_required, planned_order_rec                           │
│                                                               │
│  mrp_production_plans (MAKE) → status = pending               │
│     planned_order_rec, planned_qty, net_required              │
│                                                               │
│  mrp_run (per minggu) → status = completed                    │
│                                                               │
│  eta_week   = minggu planning (YYYY-Www)                      │
│  order_week = eta_week − vendor.lead_time_days               │
└───────────────────────────────────────────────────────────────┘
     │
     ▼
┌─ 4. APPROVE / REJECT ─────────────────────────────────────────┐
│  planning/mrp/approve | reject    (gate: can approve_mrp)     │
│  Massal via mrp_run_id ATAU plan_ids[]                        │
│  pending → approved / rejected                                │
│  (approved_by, approved_at diisi saat approve)                │
└───────────────────────────────────────────────────────────────┘
     │
     ▼
┌─ 5. GENERATE PO ──────────────────────────────────────────────┐
│  planning/mrp/generate-po  (hanya plan status = approved)     │
│  Group by VENDOR → 1 PO per vendor:                           │
│  purchase_orders (status = Pending)                           │
│     po_number = PO-MRP-<timestamp>-<vendor_id>                │
│  purchase_order_items: part_id, vendor_part_id,               │
│                        gci_part_vendor_id, qty, unit_price,   │
│                        subtotal                                │
│                                                               │
│  ⚠️ Validator:                                                 │
│   - part harus terdaftar di Incoming Part master (parts view) │
│   - vendor harus LOCAL (selain → error "Only Local POs")      │
└───────────────────────────────────────────────────────────────┘
     │
     ▼
┌─ 6. RELEASE PO ───────────────────────────────────────────────┐
│  planning/mrp/purchase-orders/{id}/release   (gate: release_po)
│  Pending/Approved → Released                                  │
│  (released_by, released_at diisi)                             │
└───────────────────────────────────────────────────────────────┘
     │
     ▼
┌─ 7. ACTUALIZE / TERIMA BARANG ────────────────────────────────┐
│  planning/mrp/purchase-orders/{id}/actualize                  │
│  (gate: can manage_purchasing)                                │
│  Hitung per item: totalShort = Σ max(0, qty − qty_received)   │
│   0  → status Closed (semua diterima)                         │
│  >0  → status Partially Received (sisa outstanding)           │
│  qty_received diperbarui saat receiving (menu Incoming).      │
└───────────────────────────────────────────────────────────────┘
```

---

## Penjelasan Tiap Tahap

### 1. Generate MRP
`MrpController::runMrpForWeek()`

- Input demand = `Forecast` **bulanan** (`period = YYYY-MM`). Satu bulan diprorata ke
  workday yang jatuh di minggu ISO berjalan.
- Part top-level dikategorikan berdasarkan **ada/tidaknya BOM aktif**:
  - Punya BOM → **MAKE** (dibuat di produksi), BOM di-explode multi-level (max 10 level,
    ada pengaman cycle, `free_issue` dilewati).
  - Tidak punya BOM → **BUY** (dibeli). Ini menangani item drop-ship / resale / raw
    material yang dibeli langsung. *(Fiks #3)*

### 2. Hitung Net Requirement
Formula inti:

```
net_required = demand
             + safety_stock
             − (on_hand + incoming)
             − on_order
```

| Variabel | Sumber | Catatan |
|---|---|---|
| `demand` | Forecast minggu itu | sudah diprorata |
| `safety_stock` | `gci_parts.safety_stock` | kolom manual per part |
| `on_hand` | `InventoryLocationStock` sum `qty_on_hand` | stok fisik |
| `incoming` | `MrpIncomingIntegrationService` | arrival (invoice_date) + receive (ata_date) |
| `on_order` | `purchase_order_items` join PO status Pending/Approved | `SUM(qty − qty_received)` |

> ⚠️ **Anti dobel hitung.** `incoming` sudah memasukkan barang yang dijadwalkan tiba.
> `on_order` hanya menghitung **sisa yang belum diterima** dari PO yang masih aktif.
> Barang yang sudah tercatat sebagai incoming tidak dihitung dua kali.

Setelah net requirement > 0, diterapkan **MOQ** dan **order_multiple**:
- Jika `net < vendor.min_order_qty` → dinaikkan ke `min_order_qty`.
- Jika `order_multiple > 0` → `ceil(net / order_multiple) * order_multiple`.

### 3. Row Plan
Hasil perhitungan disimpan ke `mrp_purchase_plans` (BUY) dan `mrp_production_plans`
(MAKE), masing-masing dengan `status = 'pending'` dan `eta_week`.

**Lead-time offset (Fiks #2):**

```
order_week = eta_week − vendor.lead_time_days
```

- `eta_week` = minggu barang **dibutuhkan** (minggu planning).
- `order_week` = minggu PO harus **diajukan** supaya tiba tepat waktu.
- Menggunakan link vendor yang sama dengan yang memasok MOQ (`gci_part_vendor`
  dengan `status = 'active'`, urut `min_order_qty` tertinggi).

### 4. Approve / Reject
`MrpController::approvePlans()` / `rejectPlans()`.

- Hanya mengubah plan yang berstatus `pending`.
- Input: `mrp_run_id` (batch per run) dan/atau `plan_ids[]`.
- Saat approve: `status = approved`, `approved_by`, `approved_at = now()`.
- Saat reject: `status = rejected`.
- **Gate:** `approve_mrp`.

### 5. Generate PO
`MrpController::generatePo()`.

- Hanya plan `status = 'approved'` yang boleh jadi PO.
- Dikelompokkan per **vendor** → satu `purchase_orders` per vendor.
- `purchase_orders.status = 'Pending'` (approval vendor adalah langkah berikutnya di menu Purchasing).
- `purchase_order_items.qty` diambil dari `planned_order_rec` (fallback `net_required`).

**Validator yang memblokir:**
- Part **harus** terdaftar di Incoming Part master (view `parts`). Kalau belum → error
  "not registered in Incoming Part master". *(Perlu `create_part` dengan part_no sama.)*
- Vendor **harus** `vendor_type = 'local'`. Kalau ada non-local → error "Only Local POs".

### 6. Release PO
`MrpController::releasePo()`.

- Hanya PO berstatus `Pending` / `Approved` yang bisa di-release.
- `status = Released`, `released_by`, `released_at = now()`.
- **Gate:** `release_po`.

### 7. Actualize
`MrpController::actualizePo()`.

- Membandingkan `qty` vs `qty_received` per item.
- `totalShort = Σ max(0, qty − qty_received)`.
- `totalShort = 0` → `status = Closed`.
- `totalShort > 0` → `status = Partially Received` (sisa outstanding tetap terlihat).
- `qty_received` diperbarui lewat alur receiving (menu Incoming).

---

## Matriks Role / Permission

Runtime `can()` membaca dari tabel **`role_permissions`** (database), bukan config.
Config `config/role_permissions.php` adalah blueprint + fallback.

| Permission | Admin | PPIC | Purchasing | Warehouse | Staff | Quality |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| `manage_planning` | ✅ | ✅ | — | — | — | — |
| `approve_mrp` | ✅ | ✅ | — | — | — | — |
| `manage_purchasing` | ✅ | — | ✅ | — | — | — |
| `release_po` | ✅ | — | ✅ | — | — | — |

> Admin selalu dapat semuanya (bypass via `Gate::before`).
> Di kode sebenarnya, `manage_inventory` & `delete_planning` hanya di admin.

---

## Sinkronisasi RBAC

Jika ada permission baru ditambahkan ke `config/role_permissions.php`, **harus** di-re-seed
supaya tabel database ikut:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

Seeder ini idempotent: truncate `role_permissions` + `roles`, lalu baca ulang config.
Aman — `users.role` adalah enum (bukan FK), jadi tidak memutus relasi user.

---

## Laporan (View)

`resources/views/planning/mrp/index.blade.php` menampilkan dua seksi tabel:

- **Buy** (`mrpDataBuy`) — part yang dibeli (`has_purchase`).
- **Make** (`mrpDataMake`) — part yang diproduksi (`has_production`).

Kolom tabel **Buy** (`table_monthly.blade.php`):

| Kolom | Keterangan |
|---|---|
| No. / Part No / Name / Spec | identitas part + badge BUY/MAKE/MIX |
| Safety | `gci_parts.safety_stock` |
| MOQ | `gci_part_vendor.min_order_qty` |
| ETA Week | `mrp_purchase_plans.eta_week` |
| Order Week | `mrp_purchase_plans.order_week` *(baru, fiks #2)* |
| Status | pending / approved / rejected |
| Stock | `on_hand` |
| Demand | `required_qty` (demand minggu) |
| Incoming | `incoming_stock` |
| Planned | `planned_order_rec` |
| End Stock | `stock + incoming − demand` |
| Net Req | `net_required` |

---

## Catatan & Batasan

1. **PO dibuat dari vendor LOCAL saja.** Sumber luar negeri belum didukung di
   `generatePo()`.
2. **Validator Incoming Part master** wajib dipenuhi; part tanpa part master akan
   diblokir saat generate PO.
3. **Pre-existing `Arrival` (model PO lama) tidak di-migrate.** Perubahan model PO →
   `purchase_orders` adalah perilaku baru; data Arrival lama tidak dibawa otomatis.
4. `qty_received` diisi lewat flow **Incoming / receiving**, bukan di MRP.
5. **Lead time** dibaca dari link vendor yang sama dengan MOQ. Jika satu part punya
   banyak vendor aktif, dipilih `min_order_qty` tertinggi (konsisten dengan kebijakan MOQ).
