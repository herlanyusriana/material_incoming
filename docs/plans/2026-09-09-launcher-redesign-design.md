# Desain: Rombak Total Frontend — Full Launcher ERP (GCI Smart App)

Tanggal: 2026-09-09 · Status: Disetujui user · Stack: Laravel + Blade + Tailwind + Alpine + Vite (tidak berubah)

## 1. Ringkasan

Navigasi sidebar lama dibuang total, diganti **full launcher 3 level** ala home screen:
`Home (grid modul) → Dashboard modul (KPI + chart + grid fitur) → Halaman fitur`.
Semua fitur yang tidak masuk daftar target 7+1 modul **dihapus bersih** (kode + tabel).
Sistem menjadi **BOM-centric** dan **trilingual (ID/EN/KO)**.

## 2. Log Keputusan (dari diskusi)

| # | Keputusan | Pilihan |
|---|---|---|
| 1 | Model navigasi | **A1 murni launcher** — tanpa sidebar/topbar nav; header halaman fitur hanya breadcrumb |
| 2 | Dashboard modul | **B2 informatif** — kartu KPI + chart + tabel "Perlu Perhatian" + grid fitur |
| 3 | Home | **H1** — murni grid modul; dashboard global lama dipensiunkan, KPI disebar ke modul |
| 4 | Scope fitur | **T1** — daftar 7 modul = target final; di luar itu dihapus |
| 5 | Kedalaman bersih-bersih | **D3** — hapus navigasi + kode + drop tabel lama (wajib backup DB sebelum migration drop) |
| 6 | Bahasa | ID / EN / KO, locale per user (`users.locale`), semua label = translation key |
| 7 | Konsistensi admin | **K1 + K2** — User & Role Management ikut design system yang sama; daftar permission disinkron dari `config/modules.php` |
| 8 | Source of truth | **BOM-centric** — Part & Machine auto-registrasi dari BOM (Master = read-only). Customer & Vendor tetap manual CRUD di Master Data |
| 9 | API | Rombak API ikut bersih; Flutter app menyusul setelah web final |

## 3. Struktur Modul & Menu (target final)

Konfigurasi tunggal: `config/modules.php` — key, label (translation key), icon, route, permission, flag `new`.

1. **Master Data**: Part Master (RO) · Customer Master · Vendor Master · Trucking Company · Machine (RO) · Locations Warehouse
2. **Marketing**: Customer Product Mapping · BOM (+routing & cycle time standar — pusat data) · Price Master · PO Customer
3. **Planning**: Forecast · Daily Demand Customer 🆕 · Production Plan 🆕
4. **Purchasing**: MRP · Purchase Order · Material Price 🆕 · In Transit Import 🆕
5. **Warehouse**: Incoming Material 🆕 · Outgoing Material 🆕 · Stock · Transfer Stock · Stock Opname (System & Actual)
6. **Production**: Daily Prod Plan FG & Mesin 🆕 · Prod Result Input (Good/NG & Downtime) 🆕 · Cycle Time by Machine 🆕
7. **Outside Process**: Subcon IN 🆕 · Subcon Out 🆕 · Request Subcon Process 🆕
8. **Admin** (khusus admin/super-admin): User Management · Role Management

🆕 = fitur baru (fase 3). Tanpa tanda = fitur lama yang bertahan (halaman isinya tidak dirombak, hanya direlink).

## 4. Arsitektur Navigasi

- `/` → redirect `/home`. `/home` = launcher (header: logo GCI, sapaan, `x-lang-switcher`, avatar → profil/logout).
- `/{module}` (mis. `/production`) = dashboard modul, template identik untuk 8 modul.
- Halaman fitur: layout tanpa sidebar; header = `x-breadcrumb` (Home / Modul / Fitur) — jalur "kembali" murni launcher.
- RBAC: tile fitur dan tile modul difilter dari permission di `config/modules.php`. Modul tanpa satu pun akses → tile tidak dirender; semuanya tertutup → halaman "hubungi admin".
- API: `GET /api/v1/modules` mengembalikan struktur launcher yang sama (dikonsumsi Flutter nanti). Endpoint lama yang mati dihapus; versi API = v1 (restart).

## 5. Dashboard Modul — KPI & Chart

Template: baris KPI (3–4 kartu) → chart (⅔) + Perlu Perhatian (⅓) → grid tile fitur. Chart pakai **chart.js** (npm).

| Modul | KPI | Chart | Perlu Perhatian (maks 5 baris) |
|---|---|---|---|
| Master Data | Total part/customer/vendor/machine | — | Part tanpa BOM |
| Marketing | PO customer bln ini, qty, harga expired | Tren PO customer 6 bln | PO customer belum diproses |
| Planning | Forecast aktif, demand hari ini, plan minggu ini | Coverage plan vs demand | Tanggal tanpa production plan |
| Purchasing | PO open, nilai PO bln, in-transit | Status PO | PO belum di-approve |
| Warehouse | Incoming hari ini, outgoing hari ini, stok < min | Movement 30 hari | Menunggu QC / selisih opname |
| Production | Output good, % NG, downtime jam | Output harian 14 hari | Cycle time actual < 80% standar BOM |
| Outside Process | Subcon out belum kembali, subcon in, request pending | In/Out 6 bln | Subcon > 14 hari |
| Admin | User aktif, jumlah role | Login 7 hari | User nonaktif > 90 hari |

## 6. Logika Domain: BOM-centric

**Engineering Master = BOM + Routing** (dikelola di Marketing): per part customer berisi material penyusun + qty/pcs, mesin standar yang boleh memproduksinya, cycle time standar mesin tsb.

- Simpan BOM → part & machine baru auto-registrasi ke master (status aktif); Master Part/Machine = read-only + edit atribut terpilih.
- MRP: net requirement = demand × BOM explosion.
- Production Plan / Daily Prod Plan: alokasi demand hanya ke mesin yang terdaftar di BOM part.
- Machine Load: kapasitas − Σ(qty × cycle time standar).
- Prod Result Input: aktual good/NG vs target plan; downtime terekam.
- KPI Cycle Time: actual vs standar BOM.
- Customer & Vendor: manual CRUD di Master Data (transaksi hanya bisa pilih yang sudah ada).

## 7. Sistem Visual (clean & responsif)

- **Warna**: primary indigo-600; gradasi indigo→purple hanya logo/tile aktif; netral slate; status emerald/amber/rose/sky. Maks 2 aksen per halaman.
- **Bentuk**: kartu rounded-2xl, tile rounded-xl, shadow-sm, border slate-200; spacing 4px scale; max-width konten 1440px.
- **Tipografi**: 1 keluarga; label 11px uppercase tracking-wide; body 14px; judul 18px; angka KPI 28px tabular-nums.
- **Tile**: tinggi 96–112px, ikon outline 24px + label, kontras AA, target sentuh ≥48px, hover lift 2px, focus ring terlihat.
- **Grid**: home 2→3→4 kolom (HP/tablet/desktop); KPI 1→2→4; tile fitur 2→3→5.
- **Komponen baru**: `x-module-tile`, `x-menu-tile`, `x-attention-table`, `x-chart`, `x-breadcrumb`, `x-empty-state`, `x-lang-switcher` (+ pertahankan `x-stat-card`, `x-page-header`).
- Light mode only; tanpa dark mode (YAGNI).

## 8. i18n

- `lang/id.php`, `lang/en.php`, `lang/ko.php`; seluruh string UI via `__()`.
- Kolom `users.locale` (default `id`); switcher di home & profil; middleware set locale.
- Tanggal/angka ikut locale. Terjemahan KO diisi terbaik lalu ditandai untuk review penutur asli.

## 9. Fase Eksekusi

**Fase 1 — Kerangka navigasi**
`config/modules.php` + komponen tile/breadcrumb + layout tanpa sidebar + `/home` + 8 dashboard modul (KPI dari data yang bertahan) + relink fitur lama yang lolos + filter RBAC + i18n framework. Hapus sidebar & dashboard global dari UI.

**Fase 2 — Bersih-bersih D3**
1) Audit daftar "boleh hapus / jangan sentuh" (cek silang API lama, job, relasi) → disetujui user.
2) Hapus route/controller/view/test mati.
3) `mysqldump` backup → migration drop tabel mati + kolom tidak dipakai.
4) Update permission seed sesuai config baru.

**Fase 3 — Fitur baru** (per modul, prioritas ditentukan user saat mulai): Daily Demand Customer, Production Plan, Material Price, In Transit Import, Incoming & Outgoing Material, Daily Prod Plan FG & Mesin, Prod Result Input, Cycle Time by Machine, BOM+routing upgrade, Subcon IN/OUT/Request, Stock Opname System vs Actual.

**Fase 4 — API v1 untuk Flutter** (menyusuli setelah web final).

## 10. Risiko

- **Data historis hilang (D3)** → backup mysqldump wajib sebelum setiap migration drop; tahap drop dipecah per modul supaya bisa di-stop.
- **Aset lama direferensikan diam-diam** (job, notification, export) → audit Fase 2 dengan grep relasi sebelum hapus.
- **Terjemahan KO kurang tepat** → review user.
- **Flutter app butuh rombak API** → disengaja; fase 4.
