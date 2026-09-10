# Panduan Alur Barang: Dari Supplier Sampai ke Produksi

**Untuk**: Operator gudang / user awam
**Aplikasi**: ERP GCI (Smart Application System)
**Terakhir diuji**: 2026-09-06 (sudah di-test end-to-end di lingkungan dev)

---

## Apa yang Akan Anda Lakukan?

Dokumen ini menjelaskan **4 langkah** untuk memproses barang material dari supplier sampai barang itu keluar dari gudang dan masuk ke produksi:

```
[1] CATAT BARANG MASUK   →   [2] TERIMA BARANG (RECEIVING)   →   [3] PUTAWAY (opsional)   →   [4] CETAK LABEL   →   [5] KELUARKAN KE PRODUKSI
     (Input Manual)              (tag + QC + simpan ke Inventory)  (pindah ke rak)         (label + QR)       (Post WH Supply)
```

Setelah langkah 2, barang sudah **tersimpan di stok gudang**. Langkah 3 membuat label fisik untuk ditempel. Langkah 4 mengeluarkan barang dari gudang ke produksi saat ada Work Order (WO) yang membutuhkan.

> **Istilah singkat**
> - **Arrival / Departure** = dokumen "barang datang dari supplier" (di menu disebut *Departure*).
> - **Receive / Receiving** = proses menimbang, membuat **tag**, melakukan QC, dan menyimpan stok awal di `RECEIVING`.
> - **Tag** = kode unik (mis. `TAG-001`) yang menempel di satu tumpukan barang di satu rak.
> - **WO (Work Order)** = perintah produksi. Produksi "meminjam" material dari gudang lewat WO.

---

## Langkah 1 — Catat Barang Masuk (Input Manual)

**Menu**: `Incoming` → `Departure` → tombol **Create / + New**
**Alamat halaman**: `/departures/create`

Halaman ini untuk mencatat **satu kiriman barang dari supplier** (satu invoice).

### A. Isi data kiriman (bagian atas)

| Kolom | Isi apa | Contoh | Wajib? |
|---|---|---|---|
| **Vendor** | Ketik nama supplier, lalu **pilih dari saran** yang muncul | `BT INTERNATIONAL CO., LTD` | Ya |
| **Invoice Number** | Nomor invoice dari supplier (harus unik) | `INV-QA-0906-001` | Ya |
| **Invoice Date** | Tanggal invoice | `2026-09-06` | Ya |
| **Currency** | Mata uang (USD / IDR / EUR / JPY) | `USD` | Ya |
| ETD / ETA JKT / ETA GCI | Tanggal estimasi (opsional, untuk import) | — | Tidak |
| Vessel / Port of Loading | Nama kapal / pelabuhan (opsional) | — | Tidak |
| Trucking Company | Perusahaan truk (opsional) | — | Tidak |
| Bill of Lading / No PEN / No AJU | Dokumen bea cukai (opsional) | — | Tidak |
| Price Term | Syarat harga (opsional) | — | Tidak |
| Notes | Catatan bebas (opsional) | — | Tidak |

> **Container (opsional)**: Kalau barang datang dalam kontainer, klik **Add Container**, isi *Container No* + *Seal Code*.
> ⚠️ Kalau **tidak** pakai kontainer, **hapus baris container kosong** (tombol *Hapus Container*) sebelum menyimpan — baris kosong akan membuat simpan gagal.

### B. Isi daftar barang (bagian bawah)

Setiap baris = **satu jenis part** yang datang.

1. **Pilih Jenis Material** (dropdown paling atas daftar barang) — mis. `STEEL IN SHEET`. Ini mengelompokkan part.
2. Setelah memilih jenis, **pilih part-nya** (dropdown *Select Size*). Saat part dipilih, kolom berikut **terisi otomatis**:
   - *Size* (mis. `0.25 X 640 X 1215`)
   - *Part No GCI* (mis. `KBTGIBPOMG9XS`)
   - *Part Name GCI* (mis. `BACK PLATE OMEGA 9`)
3. Lengkapi kolom yang **belum terisi otomatis**:

| Kolom | Isi apa | Contoh | Wajib? |
|---|---|---|---|
| **Qty Goods** | Jumlah barang datang | `100` | Ya (min. 1) |
| **Unit Goods** | Satuan barang (EA / ROLL / KGM / COIL / SHEET) | `SHEET` | Disarankan |
| **Unit Bundle** | Satuan tumpukan (PALLET / BUNDLE / BOX / BAG) | `PALLET` | Disarankan |
| **Qty Bundle** | Jumlah tumpukan (opsional) | `2` | Tidak |
| **Net Weight** | Berat bersih (kg) | `500` | Ya |
| **Gross Weight** | Berat kotor (kg) — **harus ≥ Net Weight** | `520` | Ya |
| **Total Amount** | Total harga (sesuai Currency) | `2500` | Ya |
| *Price* | **Otomatis** = Total ÷ Net Weight (jangan diisi manual) | `5.000` | Auto |

> **Aturan penting**: `Net Weight` tidak boleh lebih besar dari `Gross Weight`. Kalau muncul pesan *"Net weight harus lebih kecil atau sama dengan gross weight"*, periksa dua kolom berat itu.

4. Butuh lebih dari satu part? Klik **Add Row / +** untuk menambah baris.

### C. Simpan

Klik **Save Departure**. Kalau sukses, Anda diarahkan ke halaman daftar/detail arrival. Sistem membuat nomor arrival otomatis (mis. `ARR-2026-0003`).

✅ **Selesai Langkah 1** — kiriman barang sudah tercatat. Barang **belum** masuk stok; masih perlu di-*receive* (Langkah 2).

---

## Langkah 2 — Terima Barang (Receiving)

**Menu**: buka detail Arrival → item → **Receive**
**Alamat halaman**: `/departure-items/{id-item}/receive`

Di sini Anda **menimbang, memberi tag, dan mencatat hasil QC**. Setelah QC **Pass**, barang langsung masuk stok Inventory di lokasi virtual `RECEIVING`. Penempatan ke rak dilakukan belakangan melalui Putaway.

### Isi form receiving

| Kolom | Isi apa | Contoh | Wajib? |
|---|---|---|---|
| **Tanggal Receive** | Tanggal barang diterima | `2026-09-06` | Ya |
| **Truck No** | Nomor truk (hanya untuk vendor lokal) | — | Lokal saja |

Setiap **TAG** = satu tumpukan barang di satu rak. Ada 1 baris tag secara default; klik **+ Add TAG** untuk menambah.

| Kolom (per TAG) | Isi apa | Contoh | Wajib? |
|---|---|---|---|
| **Tag** | Kode unik tumpukan | `TAG-001` | Ya |
| **Location / Rak** | Opsional untuk kompatibilitas form. Saat Receive, lokasi ini belum dipakai untuk memindahkan stok | `K-BULK` | Tidak |
| **Qty** | Jumlah barang pada tag ini (min. 1) | `100` | Ya |
| **Qty Unit** | Satuan (harus sama dengan satuan barang) | `SHEET` | Ya |
| **Bundle Qty / Unit** | Jumlah & satuan tumpukan | `2` / `PALLET` | Disarankan |
| **Net Weight** | Berat bersih tag ini (kg) | `500` | Tidak |
| **Gross Weight** | Berat kotor tag ini (kg) | `520` | Tidak |
| **QC Status** | Hasil cek kualitas: **Pass** atau **Reject** | `Pass` | Ya |

> **Rak (Location)**: boleh dikosongkan saat Receive. Stok Pass akan tersimpan di `RECEIVING` terlebih dahulu. Rak fisik dipilih pada menu Putaway setelah barang siap dipindahkan.

### Simpan

Klik **Save Receive**. Sistem akan:
- Membuat record receive + tag
- **Menambah stok** barang di `RECEIVING` (batch = nama tag)
- Menampilkan halaman invoice yang sudah selesai di-receive

✅ **Selesai Langkah 2** — barang sudah **ada di Inventory** dan menunggu Putaway (bisa dilihat di menu Inventory/Stock).

---

## Langkah 3 — Cetak Label

**Menu**: dari daftar Receive → klik **Label / Print** pada receive yang mau dicetak
**Alamat halaman**: `/receives/{id-receive}/label`

Halaman ini menampilkan **label siap cetak** berisi:

- Logo & judul **LABEL MATERIAL**
- **Part No** (mis. `KBTGIBPOMG9XS`) & **Part Name** (mis. `BACK PLATE OMEGA 9`)
- **Material** & **Size** (mis. `STEEL IN SHEET`, `0.25 X 640 X 1215`)
- **Vendor** & **Invoice No**
- **Tag** (mis. `TAG-001`)
- **Berat** (mis. `500.00 KGM`)
- **Qty + Satuan** (mis. `100 SHEET`)
- **Tanggal**
- Kotak **IQC CHECK / STAMP / TTD** (untuk tanda tangan QC)
- **Kode QR** — berisi data lengkap (part, qty, rak, tag, dll.) untuk pemindaian

### Cara cetak

1. Pastikan data di label sudah benar.
2. Klik tombol **PRINT LABEL** → dialog cetak browser muncul → pilih printer → **Print**.
3. Tempel label fisik pada tumpukan barang (tag) yang bersangkutan.

> 💡 **Tips**: Cetak label **setelah** receiving (Langkah 2) supaya qty, berat, dan tag sudah final. Satu receive = satu label.

✅ **Selesai Langkah 3** — label tercetak & ditempel.

---

## Langkah 4 — Keluarkan Barang ke Produksi (Post WH Supply)

Barang di gudang dikeluarkan ke produksi **saat ada Work Order (WO)** yang membutuhkan material itu. Alurnya:

```
Produksi buat WO (ada BOM/daftar material)
        ↓
Produksi "Request Material"  →  sistem mengecek stok gudang & membuat alokasi
        ↓
Gudang "Post WH Supply"      →  stok gudang DIKURANGI, barang dianggap keluar ke produksi
```

### A. Prasyarat
- Ada **WO** yang sudah **request material** (status: sudah request, belum di-issue).
- **Stok gudang cukup** untuk semua material WO (tidak ada *shortage*). Kalau ada shortage, tombol tidak bisa diproses sampai stok dilengkapi.

### B. Cara mengeluarkan (Post WH Supply)

**Menu**: `Production` → `Warehouse Supply`
**Alamat halaman**: `/production/warehouse-supply`

1. Halaman menampilkan daftar **WO yang menunggu supply** (kolom: WO No, Part, Qty, tanggal, status).
2. Cari WO yang mau diproses.
3. Klik tombol **Post WH Supply** pada baris WO tersebut.
4. Sistem akan:
   - **Mengurangi stok gudang** sesuai alokasi material WO
   - Mencatat pergerakan stok bertipe **PRODUCTION_ISSUE**
   - Menandai WO sudah di-issue (`material_issued_at` terisi)

> ⚠️ **Penting**: Proses ini **mengurangi stok gudang secara permanen**. Pastikan WO dan qty sudah benar sebelum menekan tombol.

✅ **Selesai Langkah 4** — barang sudah **keluar dari gudang & masuk ke produksi**. Stok gudang berkurang, WO tercatat sudah menerima material.

---

## Ringkasan Cepat (Cheat Sheet)

| # | Langkah | Menu / Halaman | Hasil |
|---|---|---|---|
| 1 | Catat barang masuk | `Departure → Create` (`/departures/create`) | Arrival tercatat (belum masuk stok) |
| 2 | Receive + tag | `/departure-items/{id}/receive` | **Barang masuk stok gudang** |
| 3 | Cetak label | `/receives/{id}/label` | Label + QR tercetak |
| 4 | Keluarkan ke produksi | `Production → Warehouse Supply` (`/production/warehouse-supply`) | **Stok gudang berkurang**, WO di-issue |

### Urutan logika stok
- **Sebelum Langkah 2**: stok gudang **belum** bertambah.
- **Setelah Langkah 2**: stok gudang **bertambah** (di rak yang dipilih, batch = tag).
- **Setelah Langkah 4**: stok gudang **berkurang** (dikeluarkan ke produksi).

### Hal yang sering bikin simpan gagal
1. **Net Weight > Gross Weight** → pastikan berat bersih ≤ berat kotor.
2. **Baris Container kosong** → hapus baris container kalau tidak pakai kontainer.
3. **Rak (Location) tidak terdaftar** → pilih rak yang sudah ada di master data.
4. **WO ada shortage** → lengkapi stok dulu sebelum Post WH Supply.
5. **Invoice Number duplikat** → nomor invoice harus unik.

---

## Catatan Teknis (untuk developer / referensi)

- **Model utama**: `IncomingArrival` (arrival), `IncomingArrivalItem` (item), `IncomingReceive` (receive/tag), `InventoryLocationStock` (stok per rak), `ProductionOrder` (WO).
- **Stok bertambah** saat receive via `InventoryLocationStock::updateStock(..., 'RECEIVE', ...)`.
- **Stok berkurang** saat Post WH Supply via `InventoryLocationStock::consumeStock(..., 'PRODUCTION_ISSUE', ...)`.
- **Nomor arrival** dibuat otomatis oleh `IncomingArrival::generateArrivalNo()` (format `ARR-YYYY-NNNN`).
- **QR label** berisi JSON: tag, part, qty, rak, vendor, invoice, dll.

### Bug yang ditemukan & diperbaiki saat pengujian (2026-09-06)
1. **`generateArrivalNo()` tidak menghitung arrival yang di-soft-delete** → nomor arrival bisa bentrok (duplicate key). **Fix**: query sekarang memakai `withTrashed()` dan mengambil sequence maksimum dari semua arrival tahun berjalan.
2. **`IncomingReceive::$fillable` memakai nama kolom salah** (`unit_goods`/`unit_bundle`) padahal kolom DB-nya `qty_unit`/`bundle_unit` → nilai satuan receive tersimpan `NULL`. **Fix**: `$fillable` disesuaikan ke `qty_unit`/`bundle_unit`.
