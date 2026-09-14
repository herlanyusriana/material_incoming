# Report: Migrasi Master Data & Trial Daily Planning (Issue Out → Mesin)

**Tanggal:** 2026-09-10
**Lingkup:** (1) Migrasi master data workbook ke produksi, (2) Trial alur Daily Planning → Generate MO/WO → Start WO → Issue out material ke mesin.
**Sumber:** Eksekusi terverifikasi (command & query nyata), laporan trial dari operator, analisis kode.

> **Catatan integritas:** dokumen ini memisahkan dengan tegas antara fakta terverifikasi, laporan user, dan hipotesis. Tidak ada klaim yang dilebih-lebihkan.

---

## 1. Ringkasan Eksekusi

Migrasi master data workbook (`master data 20260908.xlsx`, SHA-256 terverifikasi) ke produksi **berhasil dan terverifikasi**. Namun trial alur Daily Planning sampai issue-out ke mesin menemukan masalah pada 3 tahap (Generate MO/WO, Start WO, Issue out) dengan gejala *data tidak nyambung* dan *tidak merespons*.

**Penyebab paling mungkin (hipotesis utama): migrasi mengimpor master data + BOM, tetapi TIDAK mengimpor stok gudang.** Alur issue-out ke mesin mensyaratkan stok (`inventory_location_stock`) yang saat ini belum pernah terisi via receiving. Rincian di bagian 4.

---

## 2. Migrasi Data (TERVERIFIKASI)

### 2.1 Sumber & metode
- Workbook: `master data 20260908.xlsx`
- SHA-256: `1a90987abe176b2d6db848aac18b1a6227ae5b25eee0ccf1d664d8ca1fc8a4ac` — cocok dengan plan, diverifikasi ulang di produksi sebelum import
- Alat: `App\Services\MasterDataWorkbookImporter` via Artisan `master-data:import {path} --dry-run` → import
- Sifat: atomik (satu transaksi), idempoten (import 2x hasil identik — terbukti di lokal & produksi)
- Dry run produksi **100% identik** dengan lokal sebelum import real dieksekusi

### 2.2 Hasil di produksi (setelah import real)

| Entitas | Jumlah | Keterangan |
|---|---|---|
| FG part | 144 | 148 baris workbook, 4 baris duplikat → terserap unik |
| Material generic (RM) | 85 | kolom "Material Part #" |
| Part substitusi supplier (RM) | 324 | kolom "Subs Part #", 329 baris − 5 duplikat |
| WIP part | 239 | kolom "Parent Part No." BOM |
| Vendor | +2 baru | 24 dari 26 supplier sudah ada → **terpreservasi**, tidak duplikat |
| Machine | +24 baru | kode deterministik `MC-<NAMA>` |
| BOM aktif | 55 | satu per FG |
| BOM line | 403 | 405 baris workbook − 2 baris `#N/A` rusak |
| Substitusi ter-link | 580 row / 151 line | setiap line material generic terhubung semua substitusi supplier |
| vendor_parts + gci_part_vendor | 327 / 327 | dua bridge tabel sinkron 100% |

### 2.3 Rekonsiliasi (produksi)
- 0 duplikasi `part_no`, 0 orphan foreign key (bom, component, wip, machine, substitutes, vendor)
- 0 sisa UOM `PCS` (dinormalisasi → `PCE`; KGM/SHEET/ROLL dipertahankan)
- Master terpreservasi: users, customers, trucking, trucks, vendor lama — tidak disentuh importer
- `make_or_buy`: MAKE 219 / BUY 151 / FREE_ISSUE 10 / SUBCON 23
- 2 baris BOM dilewati **karena data workbook rusak** (`#N/A` hasil XLOOKUP mati di baris 339 & 341 — child "PIN HOLLOW" dan "BRACKET HL" tanpa part number). Ini keterbatasan data sumber, bukan error importer.

### 2.4 Catatan schema
- Migration `2026_09_10_000001_add_missing_master_import_columns` menambahkan `vendors.vendor_code` & `boms.bom_no` yang hilang (drift schema) — jalan di lokal & produksi.

---

## 3. Temuan Trial Daily Planning (DILAPORKAN USER)

User melakukan trial alur **Daily Planning → Generate MO/WO → Start WO (material gate) → Issue out ke mesin**. Hasil laporan user:

| Tahap | Gejala dilaporkan |
|---|---|
| Generate MO/WO | Data tidak nyambung; tidak merespons |
| Start WO (material gate) | Data tidak nyambung; tidak merespons |
| Issue out ke mesin | Data tidak nyambung; tidak merespons |

⚠️ Detail pesan error spesifik (teks/screenshot) **belum tersedia** — report ini menganalisis berdasarkan struktur kode + kondisi data produksi. Konfirmasi root cause butuh log produksi (bagian 5).

---

## 4. Analisis Akar Masalah (HIPOTESIS — berbasis kode)

### 4.1 Stok gudang kosong / tidak nyambung dengan master baru [HIPOTESIS UTAMA]
Migrasi mengimpor **master data + BOM saja**. Tidak ada stok yang ikut diimpor (dan tidak boleh — stok harus lahir dari receiving yang valid). Akibatnya di produksi saat ini:
- `inventory_location_stock` hanya berisi stok lama (label part lama) atau kosong
- Alur **issue out** mensyaratkan stok per tag per lokasi; tanpa stok, supply-all → scan → post tidak punya bahan
- Alur **Start WO** punya material gate: `TEMP_ALLOW_START_WITHOUT_WH_SUPPLY = false` (mati) di `StartProductionController` — WO tanpa supply gudang **memang diblokir** dari desain

**Artinya: kondisi "tidak bisa issue out" saat ini adalah perilaku sistem yang benar, bukan bug** — prasyaratnya (receiving) belum dijalankan di produksi.

### 4.2 Data lama (seed/restore) vs master baru [HIPOTESIS]
Terdeteksi ada data lama hasil restore/seed di DB (contoh part WIP lama: `MAZ65643601-PG`, `MIFA62123401-PG`, `MFA62123401-PG`). Risikonya:
- **Auto-populate** Daily Planning menarik dari delivery requirement/forecast/customer PO lama → planning line mereferensikan part lama yang tidak punya BOM baru → Generate MO/WO menghasilkan WO "tanpa material breakdown" → terlihat sebagai *data tidak nyambung*
- Planning session/line yang dibuat **sebelum** migrasi tetap memegang `gci_part_id` lama

### 4.3 Machine duplikat [HIPOTESIS — perlu verifikasi]
Importer membuat 24 machine baru dengan kode `MC-<NAMA>`. Jika tabel `machines` produksi sudah berisi machine lama dengan nama sama tapi kode berbeda, terjadi **duplikasi nama**: BOM baru menunjuk machine baru, sedangkan order/planning lama menunjuk machine lama → Start screen memiliki flag `machine_mismatch` (`StartProductionController` memang menghitung ini) → *data tidak nyambung* saat Start WO.

### 4.4 Tidak merespons [HIPOTESIS]
Kemungkinan timeout di VPS: explode BOM + query substitusi per line sekarang jauh lebih berat (400+ line BOM, ratusan substitusi) dibanding sebelumnya (DB hampir kosong). Generate MO/WO loop per line + `getDailyRequirements` bisa melebihi `max_execution_time` PHP-FPM → request mati diam tanpa pesan.

---

## 5. Langkah Konfirmasi (butuh akses VPS)

Jalankan di produksi, paste hasilnya untuk konfirmasi tiap hipotesis:

```bash
cd /var/www/material_incoming

# 5.1 Error terakhir di log Laravel (bukti "tidak merespons")
tail -200 storage/logs/laravel.log | grep -E "ERROR|Exception" | tail -20

# 5.2 Cek machine duplikat (hipotesis 4.3)
mysql -u"$U" -p"$P" "$DB" -e "
SELECT name, COUNT(*) c FROM machines GROUP BY name HAVING c > 1;
SELECT COUNT(*) total_machines FROM machines;"

# 5.3 Cek stok (hipotesis 4.1)
mysql -u"$U" -p"$P" "$DB" -e "
SELECT COUNT(*) stok_rows, COALESCE(SUM(qty_on_hand),0) total_qty FROM inventory_location_stock;"

# 5.4 Cek planning line lama vs master baru (hipotesis 4.2)
mysql -u"$U" -p"$P" "$DB" -e "
SELECT l.id, l.session_id, l.gci_part_id, p.part_no
FROM production_planning_lines l
LEFT JOIN gci_parts p ON l.gci_part_id = p.id
ORDER BY l.id DESC LIMIT 20;"

# 5.5 WO hasil trial Generate MO/WO
mysql -u"$U" -p"$P" "$DB" -e "
SELECT id, order_number, gci_part_id, process_name, machine_id, status, created_at
FROM production_orders ORDER BY id DESC LIMIT 10;"
```

---

## 6. Rekomendasi Urutan Perbaikan

1. **Jalankan verifikasi bagian 5** — konfirmasi hipotesis dengan bukti sebelum mengubah apa pun
2. **Receiving dulu, issue out belakangan**: alur yang benar untuk trial adalah Receive material (web atau APK) → stok terbentuk → baru Supply/Issue out ke mesin. Trial issue-out tanpa stok pasti gagal
3. **Bersihkan machine duplikat** (jika terkonfirmasi): mapping ulang `bom_items.machine_id` ke machine lama, hapus machine `MC-` duplikat — ATAU biarkan yang baru dan matikan seed lama
4. **Planning session baru**: buat session Daily Planning **setelah** migrasi (jangan pakai session/line lama) supaya semua referensi part valid
5. **Bersihkan data seed lama** (`MAZ/MIFA/MFA…-PG`, dll.) jika memang bukan data nyata — bisa jadi sumber "data tidak nyambung" di banyak layar
6. **Timeout**: jika log menunjukkan deadline/timeout, naikkan `max_execution_time` atau pecah proses Generate MO/WO per-line (endpoint `generateMoWoLine` sudah tersedia)

---

## Lampiran A — Rollback migrasi (jika diperlukan)

```bash
# Backup sudah dibuat sebelum import:
mysql -u"$U" -p"$P" "$DB" < ~/db_backup_20260910_1821.sql
```

## Lampiran B — File terkait
- Plan migrasi: `docs/superpowers/plans/2026-09-10-master-data-workbook-import.md`
- Importer: `app/Services/MasterDataWorkbookImporter.php`
- Command: `app/Console/Commands/ImportMasterDataWorkbook.php`
- Test: `tests/Feature/MasterDataWorkbookImporterTest.php` (8 passing)
- Commit: `87cda6e` (importer) → `f51eb6e` (adaptasi workbook asli)
