# Departure (Incoming Material) — Form Fields & Master Data

> Dokumen ini menjelaskan **form Departure / incoming material (import)** di proyek
> `material_incoming`, beserta **master data yang wajib tersedia** sebelum bisa membuat
> sebuah Departure. Berguna sebagai acuan setup data (seeding) dan pengembangan form.

---

## 1. Apa itu "Departure"?

Di aplikasi ini nama **"Departure"** dipakai untuk **record penerimaan material import /
incoming material**. Istilah "Departure" = form **arrival (import)**.

| Aspek | Nilai |
|---|---|
| Controller | `App\Http\Controllers\ArrivalController` |
| View form | `resources/views/arrivals/create.blade.php` |
| View list | `resources/views/arrivals/index.blade.php` |
| Route prefix | `departures.*` (mis. `departures.create`, `departures.store`) |
| Tabel header | `incoming_arrivals` |
| Sidebar group | **Incoming Material** |

> Catatan: pastikan `vendor_id` yang dipilih bukan tipe `local`, karena query vendor di
> form hanya mengambil `vendor_type != 'local'`.

---

## 2. Field-form Departure (Create)

Form dikelompokkan dalam 5 blok. Untuk field yang datanya berasal dari **master data**
ditandai dengan 🗄️.

### 2.1 Vendor & Invoice

| Field | Label | Tipe | Sumber |
|---|---|---|---|
| `vendor_name` | Vendor Name | text | 🗄️ **Vendor** |
| `vendor_id` | (vendor terpilih) | hidden | 🗄️ **Vendor** |
| `invoice_no` | Invoice No | text | bebas |
| `invoice_date` | Invoice Date | date | bebas |
| `currency` | Currency | select | hardcoded |

### 2.2 Shipment Schedule

| Field | Label | Tipe | Sumber |
|---|---|---|---|
| `etd` | ETD | date | bebas |
| `eta` | ETA JKT | date | bebas |
| `eta_gci` | ETA GCI | date | bebas |
| `vessel` | Vessel | text | bebas |
| `port_of_loading` | Port of Loading | text | bebas |

### 2.3 Transport

| Field | Label | Tipe | Sumber |
|---|---|---|---|
| `trucking_company_id` | Trucking Company | select | 🗄️ **TruckingCompany** |
| `containers[][container_no]` | Container No | text (dinamis) | bebas |
| `containers[][seal_code]` | Seal Code | text (dinamis) | bebas |

> 1 container = 1 seal code. 1 invoice bisa punya banyak container.

### 2.4 Documents & Notes

| Field | Label | Tipe | Sumber |
|---|---|---|---|
| `bl_no` | Bill of Lading | text | bebas |
| `bl_status` | Bill of Lading Status | select | bebas |
| `bl_file` | Upload BL | file (pdf/image) | bebas |
| `pen_no` | Nomor PEN | text | bebas |
| `pen_date` | Tanggal No PEN | date | bebas |
| `aju_no` | Nomor AJU | text | bebas |
| `price_term` | Price Term | text | bebas |
| `notes` | Notes | textarea | bebas |

### 2.5 Departure Items (baris dinamis "Material & Part")

Setiap baris item:

| Field | Label | Sumber |
|---|---|---|
| `size` | Size | 🗄️ **VendorPart** |
| `part_id` | Part No GCI | 🗄️ **VendorPart** |
| `qty_goods` | Qty Goods | input |
| `unit_goods` | Unit Code / Satuan | 🗄️ **VendorPart** (unit) |
| `unit_bundle` | Jenis Package | input |
| `qty_bundle` | Qty Package | input |
| `unit_weight` | KGM | input |
| `weight_nett` | Net Weight (KGM) | input |
| `weight_gross` | Gross Weight (KGM) | input |
| `price` | Price (auto) | 🗄️ **VendorPart** |
| `total_amount` | Total Price | kalkulasi |
| `material_group` | Jenis Material / Part Name Vendor | 🗄️ **VendorPart** |
| `notes` | Vendor Part List | 🗄️ **VendorPart** |

> Baris item memakai **picker VendorPart** (autocomplete). Ada tombol **Sync Part Catalog**
> dan label *Automated HS Code*.

---

## 3. Master Data Wajib

Untuk bisa membuat Departure, siapkan 3 master berikut. Yang pertama & kedua adalah
**prasyarat keras** (tanpa ini form tidak bisa diisi penuh).

### 3.1 Vendor — 🗄️ REQUIRED

| | |
|---|---|
| Model | `App\Models\Vendor` |
| Tabel | `vendors` |
| Query form | `where('vendor_type', '!=', 'local')->orderBy('vendor_name')` |
| Keperluan | picker `vendor_name` / `vendor_id` |
| Validasi | `vendor_id` **required**, `exists:vendors,id` |

⚠️ Vendor bertipe `local` **tidak muncul** di form ini.

### 3.2 VendorPart — 🗄️ REQUIRED (untuk items)

| | |
|---|---|
| Model | `App\Models\NewSchema\Core\VendorPart` (extends `BaseModel`) |
| Tabel | `vendor_parts` |
| Query form | `where('status', 'active')->with('vendor')` |
| Keperluan | sumber baris **Material & Part**: part_no, name, size, unit, price, material_group, vendor_part_list |
| Relasi | `belongsTo(Vendor)` — scoped **per vendor** |

⚠️ Belum ada `vendor_parts` aktif → bagian items tidak bisa memilih part. Ini dependency
utama.

### 3.3 TruckingCompany — 🗄️ opsional

| | |
|---|---|
| Model | `App\Models\NewSchema\Outgoing\TruckingCompany` (extends `BaseModel`) |
| Tabel | `trucking_companies` |
| Query form | `where('status', 'active')->orderBy('company_name')` |
| Keperluan | dropdown Trucking |

---

## 4. Yang BUKAN Master Data

Field berikut bukan dari tabel master (free-text / hardcoded / file):

- **Currency** → dropdown **hardcoded**: `USD` / `IDR` / `EUR` / `JPY` (tidak ada tabel `currencies`)
- ETD, ETA, ETA GCI, Vessel, Port of Loading → text
- Containers + Seal Code → text (baris dinamis)
- BL No / BL Status / BL File, PEN No / PEN Date, AJU No, Price Term, Notes → text / file

---

## 5. Validasi `store` (ArrangeController::store)

Aturan utama saat submit:

```
invoice_no   => required, string, max:255, unique:incoming_arrivals.invoice_no
invoice_date => required, date
vendor_id    => required, exists:vendors,id
vendor_name  => nullable
currency     => required, string, max:10
```

- `invoice_no` dinormalisasi menjadi **uppercase**.
- Data disimpan dalam `DB::transaction`.
- Container dinormalkan (`containers[]` / `container_numbers`) + `seal_code`.

---

## 6. Urutan Setup Data (Dependency)

```
1. Vendor          → isi vendor import (vendor_type != 'local')
2. VendorPart      → isi part per vendor, status = 'active'
3. TruckingCompany → isi list trucking, status = 'active'
4. Create Departure (incoming_arrivals)
```

---

## 7. Catatan Lanjutan (Receive)

Setelah Departure dibuat, proses **Receive** (penerimaan barang → stok) akan membutuhkan
master tambahan:

- **Location / Warehouse** → penentuan lokasi penyimpanan
- **Part** → yang punya stok (untuk pengelolaan saldo / stock)

> Master ini **tidak** dibutuhkan untuk *create departure*, tapi untuk langkah berikutnya
> dalam alur incoming material.
