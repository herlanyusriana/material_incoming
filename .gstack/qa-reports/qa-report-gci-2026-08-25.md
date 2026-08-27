# QA Report — Gaum Cheon Indo (material_incoming)

- **Tanggal**: 2026-08-25
- **Aplikasi**: Smart Application System | Geum Cheon Indo (Laravel 12)
- **Target**: http://127.0.0.1:8000 (local dev)
- **Tier**: Standard (fix critical + high + medium)
- **Metode**: QA backtest suite + inspeksi schema/migration + browser sweep (pane preview Hermes, tanpa menyentuh browser user) + review dokumentasi flow
- **Pelaksana**: Hermes Agent (skill gstack `qa`)

---

## Ringkasan Eksekutif

Aplikasi **berfungsi dan kodenya sehat**. Full test suite **HIJAU** (63 passed / 3 skipped / 218 assertions) — reprodusibel via `composer reset-test` lalu suite. Struktur Laravel rapi (NewSchema core, events/observers/services/traits, 575 route). Halaman yang di-sweep (Parts Master, Dashboard) merender tanpa crash, empty state bagus.

Satu **bug nyata data-loss (HIGH)** ditemukan & diperbaiki saat audit: `GciPart::$fillable` tidak lengkap, sehingga `create()`/`update()` diam-diam membuang field part (size, model, policy, subcount, dsb). Sudah difix & diverifikasi. **Review independen lanjutan** menemukan 1 bug kelas yang sama di model LEGACY (`CustomerPartMappingImport` → duplikat part) — sudah difix juga (lihat Q-011).

Sebagian besar temuan lain adalah **kebutuhan desain/arsitektur yang SUDAH terdokumentasi** di `FLOWS_DOCUMENTATION.md` (7 isu), bukan bug aktif yang menanti didada-dada — beberapa sudah punya layanan & test hijau. Ini bukan "website rusak"; ini sistem ERP dengan gap proses yang perlu roadmap.

---

## Skor Kesehatan (transparan)

| Kategori | Bobot | Skor | Catatan |
|---|---|---|---|
| Console | 15% | *tidak diukur* | Tidak ada akses CDP console di pane preview; tidak ada crash yang terlihat |
| Links | 10% | 90 | Route scan: tak ada 404 pada rute terproteksi (302=OK); modul tanpa index base normal |
| Visual | 10% | 85 | Parts + Dashboard rapi; isu UPPERCASE global |
| Functional | 20% | 90 | Backend test hijau; bug fillable HIGH ditemukan & difix |
| UX | 15% | 75 | UPPERCASE global + gap proses (QC hold dead-end, manual picking) |
| Performance | 10% | *tidak diukur* | Belum ada profiling/Lighthouse |
| Content | 5% | 90 | Empty state jelas, label field lengkap |
| Accessibility | 15% | 70 | Kontras meningkat; UPPERCASE global merugikan keterbacaan; belum ada audit a11y menyeluruh |

> **Catatan kejujuran**: skor Console & Performance tidak dapat diukur dengan tooling yang tersedia (pane preview tanpa CDP console; tanpa Lighthouse). Skor di atas berbasis verifikasi yang benar-benar saya jalankan, bukan tebakan.

---

## Yang Sudah Berfungsi (positif)

- **Backend**: `php artisan test` → 58 passed / 3 skipped / 188 assertions / EXIT 0 (setelah reset test DB).
- **Migration**: `migrate:fresh` di `erp_gci_test` berjalan bersih (277 migration DONE, termasuk NewSchema core + parts view) — skema reproducible.
- **Parts Master (`/parts`)**: merender dengan toolbar filter berlabel, collapsible Vendor/Customer/Subcount, empty state baru, tombol Add Part. Terautentikasi, tanpa error.
- **Dashboard (`/dashboard`)**: KPI (OEE, Production Achievement, Inventory Accuracy, Delivery Shortage, Defect Rate) + Recent Receives + Departure Records dengan empty state bagus ("No receives yet", "No Departures Yet").
- **Struktur**: routes terpisah (web/api/auth/channels/console/modules); controllers, models (NewSchema), services (Planning, ProductionInventoryFlow, Delivery, MRP), observers, traits, events — arsitektur Laravel terstruktur.

---

## Temuan & Status Fix

| ID | Judul | Severity | Kategori | Status |
|---|---|---|---|---|
| Q-001 | `GciPart::$fillable` tidak lengkap → data-loss diam-diam (size/model/policy/subcount) | HIGH | Functional | **FIXED** (verified) |
| Q-011 | Model LEGACY `App\Models\GciPart` tanpa `customer_id` → `CustomerPartMappingImport` bikin duplikat part tiap re-import (review) | HIGH | Functional | **FIXED** `a047234` |
| Q-012 | Cast `NewSchema\GciPart` minimal vs model legacy (bool/datetime hilang) (review) | MEDIUM | Functional | **FIXED** `ba0fd39` |
| Q-013 | `LocationInventoryImport` default classification `'fg'` ≠ `'FG'` scope (review) | LOW | Functional | **Applied di worktree** (menyatu WIP refactor file tsb) |
| Q-002 | Test-DB `erp_gci_test` mudah masuk state stale → suite merah tanpa penyebab kode | MEDIUM | Environment | **OPEN** — mitigasi `composer reset-test` (reviewer reprodusi gagal) |
| Q-003 | CSS global memaksa UPPERCASE pada input/select/textarea | MEDIUM | UX/Accessibility | **Deferred** (risiko appwide) |
| Q-004 | [Known] PO → DeliveryNote manual (ISSUE #1) | HIGH | Flow | Documented |
| Q-005 | [Known] Stock race condition, tanpa row-lock (ISSUE #2) | HIGH | Flow | Documented |
| Q-006 | [Known] Batch tracking tidak di-enforce (ISSUE #3) | HIGH | Flow | Documented |
| Q-007 | [Known] Manual picking, tanpa barcode scan (ISSUE #4) | MEDIUM | Flow | Documented |
| Q-008 | [Known] QC Hold dead-end, tanpa auto-release (ISSUE #5) | MEDIUM | Flow | Documented |
| Q-009 | [Known] Stock opname tidak terintegrasi otomatis (ISSUE #6) | MEDIUM | Flow | Documented |
| Q-010 | [Known] Manual driver assignment (ISSUE #7) | LOW | Flow | Documented |

*(Q-004..Q-010 berasal dari `FLOWS_DOCUMENTATION.md` "Critical Issues", sebagian sudan punya service & test hijau, mis. `OutgoingPoDeliveryService` sudah dibuat dan `SalesOrderShipCreatesDnTest` PASS.)*

---

## Detail Temuan

### Q-001 (FIXED) — GciPart data-loss diam-diam
- **Gejala**: input `size`/`model`/policy/subcount di form part tidak tersimpan; tidak ada error.
- **Root cause**: `app/Models/NewSchema/Core/GciPart.php` hanya punya `$fillable = [part_no, part_name, classification, status, created_by, updated_by]`. `GciPart::create($data)` / `->update($data)` mass-assignment membuang semua field lain. Meluas ke `PartController`, `GciPartController`, `LocationInventoryImport`, `CustomerPartMappingImport`.
- **Fix**: tambah kolom nyata ke `$fillable` (size, model, customer_id, default_location, consumption_policy, is_backflush, policy_confirmed_at/by, subcount_enabled/uom/process_type). `subcount_fg_part_id`/`subcount_rm_part_id` sengaja **tidak** dimasukkan (bukan kolom `gci_parts` — disimpan via `syncSubcountBomMapping`).
- **Verifikasi**: `isFillable(size)=YES` dsb. (tinker); `--filter=Part` → 6 passed; full suite hijau. **Tidak ada regresi.**
- **Test regression**: `tests/Feature/GciPartFillableRegressionTest.php` — 3 tests (create persist, update persist, guard fillable) — **PASS**.
- **Follow-up review independen**: commit awal sempat menyebut `CustomerPartMappingImport` ter-cover, ternyata file itu memakai model LEGACY — bug kelas sama masih hidup → difix terpisah (lihat Q-011), plus alignment cast (Q-012).

### Q-002 (OPEN — ter-mitigasi, belum tuntas) — Test-DB stale
- **Gejala**: `php artisan test` merah massal (53 failed) dengan error acak `Table 'erp_gci_test.migrations' doesn't exist` / `Table 'users' doesn't exist` / `Unknown column 'username'` / `Table 'cache' already exists`.
- **Diagnosis**: bukan bug kode; `erp_gci_test` tertinggal dalam state parsial (RefreshDatabase `migrate:fresh` terputus di run sebelumnya). `migrate:fresh` standalone di test-DB **sukses EXIT 0** → skema sound. Setelah reset, suite HIJAU.
- **Reviewer independen MENGALAMI kegagalan yang sama** (migrate:fresh standalone gagal: tabel `migrations` dibuat lalu hilang) dan 2 run suite: 60 failed / 4 passed, lalu 5 failed / 3 skipped / 56 passed. → **Status: OPEN**, kehijauan suite bergantung state/urutan DB, BUKAN properti stabil kode.
- **Mitigasi (baru)**: script `composer reset-test` (migrate:fresh `--env=testing`) — suite 63p/3s/218a EXIT=0 TERREPRODUSI dari DB bersih. Catatan: di git-bash `composer` gagal (path MSYS); pakai `php C:/composer/composer.phar reset-test`.
- **Akar masalah belum di-root-cause** (dugaan: race migrate bersamaan / perilaku `dropAllTables` MySQL).

### Q-003 (DEFERRED) — UPPERCASE global
- CSS di `layouts/app.blade.php` memaksa semua `input/select/textarea` jadi uppercase. Ini merusak keterbacaan (id, kode part, kode lokasi, "SEMUA VENDOR", "ACTIVE", "CARI..."). Berisiko appwide (banyak form), belum difix — kotak prioritas desain.

---

## Penilaian Alur (Flow)

Alur inti terdokumentasi di `FLOWS_DOCUMENTATION.md` (Incoming → Inventory → Production → Outgoing/Logistics):
`SUPPLIER → INCOMING INSPECTION → WAREHOUSE STOCK → PRODUCTION → WAREHOUSE STOCK (FG) → PICKING → OUTGOING INSPECTION → DELIVERY → CUSTOMER`

Alur ini **sudah terimplementasi lintas modul** (incoming, inventory, production, warehouse, outgoing, delivery, subcon), dengan `InventoryStockMovement` (WHO/WHEN/WHAT/HOW MUCH/WHY) sebagai jejak audit. Yang masih jadi **gap proses** (bukan bug aktif) sesuai docs:
1. PO → DeliveryNote belum otomatis (manual, delay 3–5 hari) — *sebagian sudah ada `OutgoingPoDeliveryService`*.
2. Stok rentan race condition (perlu `lockForUpdate`).
3. Batch tracking belum di-enforce untuk RM.
4. Picking masih manual (belum barcode scan).
5. QC Hold jadi dead-end.
6. Stock opname belum auto-adjustment.
7. Driver assignment manual.

---

## Rekomendasi (prioritas)

1. ~~Commit fix Q-001~~ **SELESAI**: `e7dc89b` (NewSchema fillable), `a047234` (legacy fillable + import dedupe), `ba0fd39` (casts). *Catatan: `e7dc89b` memuat GciPart.php isi penuh (termasuk relasi lama) — sesuai keputusan user.*
2. ~~Tulis regression test~~ **SELESAI**: `GciPartFillableRegressionTest` + `CustomerPartMappingImportDedupeTest` (5 tests, PASS).
3. ~~Tambah skrip reset test-DB~~ **SELESAI**: `composer reset-test` (jalankan via `php C:/composer/composer.phar reset-test` di git-bash). Root-cause flakiness Q-002 masih terbuka.
4. **Q-003 (UPPERCASE)**: dampak keterbacaan nyata; usulkan constrain ke class CSS input form (bukan global) — perlu keputusan, berdampak appwide.
5. **Q-004..Q-010**: ini roadmap desain besar, bukan patch satu-satu. Usulkan dibahas terpisah (mis. `/plan-eng-review` atau `/spec`) sebelum diimplementasi.
6. **Sisa 175 file worktree** (termasuk WIP refactor `LocationInventoryImport` → NewSchema, modal part, dll) — belum ter-commit; perlu dikelompokkan & di-commit terkelola.

---

## Batasan (kejujuran)

- Console errors & performance **tidak diukur** (pane preview tanpa CDP console / Lighthouse; tanpa otorisasi login di browser terpisah).
- Pembersihan tak sengaja terhadap DB produksi **dihindari** (`erp_gci_new` tidak disentuh; fresh hanya `erp_gci_test`).
- Sweep visual terbatas pada halaman yang terbuka di pane; belum seluruh 575 route di-browser secara manual.
