# Laporan Progress Project Material Incoming

**Tanggal:** 2026-09-05  
**Repository:** material_incoming  
**Branch:** main

---

## Ringkasan

Project ERP Material Incoming telah melalui tahap refactoring besar dari arsitektur legacy ke NewSchema domain-driven design. Semua modul utama sudah berjalan di atas struktur baru, dengan fokus pada stabilitas traceability, produksi, dan integrasi subcon.

## Capaian Utama (Agustus–September 2026)

| Modul | Status | Keterangan |
|-------|--------|------------|
| **Incoming (Arrival/Receive)** | Stabil | Create arrival, receive, QR label, print invoice, import dokumen, dropdown size diperbaiki. |
| **Inventory** | Stabil | Stock card, location stock, inventory movements, transfer, adjustment, stock opname. |
| **Production** | Stabil | WO, routing, hourly reports, backflush, material issue, handover. |
| **Planning (Forecast & MRP)** | Stabil | Forecast list, MRP dengan family filter, pivot table bulanan. |
| **Outgoing** | Stabil | DO, DN, picking, trucking, standard packing. |
| **Subcon** | Stabil | PO subcon, receive subcon, traceability. |
| **Administrasi** | Stabil | User & role management, permission. |
| **QR Label / Traceability** | Diperbaiki | Batch identity sekarang konsisten (tag == batch_no). |
| **Part Master** | Stabil | CRUD GCI Part, vendor part, BOM, substitute, subcount mapping. |

### Statistik

- **Routes:** 99+ (incoming, inventory, master_data, outgoing, planning, production, purchasing, subcon, warehouse)
- **Controllers:** ±80 file
- **Models:** 70+
- **Migrations:** 289
- **Commands:** 8
- **Services:** 7

---

## Alur End-to-End (Incoming Material → FG Dispatch)

### 1. Incoming Material (Receiving)

1. **Create Arrival** – User membuat kedatangan (arrival) dengan data vendor, kontrak, item (part, qty, uom).
2. **Receive** – Setelah barang fisik tiba, user melakukan receive per item. Sistem menghasilkan QR label (tag RCV-xxx) sebagai identitas batch dan mem-posting QC-pass ke lokasi virtual `RECEIVING`.
3. **Putaway** – Jika barang siap ditempatkan, stok dipindahkan dari `RECEIVING` ke lokasi gudang fisik (`inventory_location_stock`) dengan `batch_no = tag`, dan riwayatnya dicatat di `inventory_stock_movements`.
4. **Quality Inspection** (Opsional) – Inspeksi kualitas dicatat di `arrival_inspections` dan `container_inspections`.

### 2. Inventory Management

- Stock tersedia di `inventory_location_stock` dengan batch dan lokasi spesifik.
- Monitoring via **Stock Card** (per part, per lokasi, per batch).
- Transfer antar lokasi dan adjustment tersedia.
- Stock Opname periodik.

### 3. Planning (Forecast & MRP)

1. **Forecast** – User input forecast demand (per part, per bulan) melalui `ForecastDocument` dan `ForecastDocumentRow`.
2. **MRP** – Sistem menjalankan MRP berdasarkan forecast, menghasilkan:
   - **Purchase Plan** (BUY) untuk RM.
   - **Production Plan** (MAKE) untuk FG/WIP.
3. **Family Filter** – MRP dan Forecast bisa difilter berdasarkan product family (Comp Base, Back Plate, Reinforce, Tray Drip, Small Part) dari `part_name`.

### 4. Production (Work Order)

1. **Material Request** – Berdasarkan MRP, sistem membuat material request ke gudang.
2. **Warehouse Supply** – Gudang menyuplai material ke produksi.
3. **Start Production** – WO dimulai dengan operator, mesin, dan proses.
4. **Hourly Reports** – Operator mencatat output aktual (`qty_actual`, `qty_ng`) per jam.
5. **Backflush** – Konsumsi material otomatis saat output FG dicatat, mengurangi stock lokasi.
6. **Handover** – Transfer WIP antar proses.
7. **Finish WO** – WO selesai, FG masuk stock.

### 5. Outgoing (Delivery & Picking)

1. **Delivery Order (DO)** – Daftar permintaan pengiriman dari customer.
2. **Picking** – Operator scanning lokasi dan part, mengurangi stock FG.
3. **Delivery Note (DN)** – Surat jalan dan packing list dicetak.
4. **Trucking** – Pengiriman dijadwalkan.
5. **Shipment** – Barang keluar, stock berkurang.

### 6. Subcon (Outsourcing)

- Subcon PO dibuat untuk parts yang diproses di vendor luar.
- Material dikirim ke subcon.
- Hasil jadi diterima kembali, dicatat batch dan stock.

---

## Fitur Unggulan

### Incoming & Inventory
- Import dokumen (Excel) untuk receive.
- Cetak label QR untuk setiap receive.
- Print invoice, inspection report, detail arrival.
- Stock card dengan pergerakan per batch.
- Multi-lokasi dan multi-batch.
- Stock opname dengan barcode scanning.
- QR label konsisten sebagai `batch_no`.

### Production
- BOM aktif dengan routing (proses, mesin, material).
- Work Order dengan status (released, in_production, paused, material_hold, dll).
- Operator board per mesin.
- Hourly reporting dengan breakdown `actual`, `ng`, `ng_reason`.
- Material issue & handover antar proses.
- Backflush konsumsi material.
- Support WIP stock antar proses.
- Integrasi dengan ProductionGciApi (Android app).

### Planning
- Forecast list dengan pivot bulanan.
- MRP dengan family filter dan badge.
- History audit.

### Outgoing
- Delivery Order, Delivery Note, Picking FG.
- Print surat jalan, packing list, invoice.
- Support standard packing.

### Administrasi
- User & role management dengan permission granular.
- Dashboard dan logs.

---

## Perbaikan Terbaru (September 2026)

| Commit | Deskripsi |
|--------|-----------|
| `019e029` | fix(arrivals): include gci_parts.size in API and prioritize it in size dropdown |
| `8efb56c` | fix(arrivals): size dropdown uses register_no before part_no fallback |
| `ee9c80e` | fix(production): drop nonexistent batch_no predicate in backflush traceability |
| `e04627a` | fix(inventory): backfill receive tag onto existing location-stock batch |
| `ca236a4` | fix(production): backflush movement lookup uses from_location_code |
| `17c6afa` | fix(production): write production order number as FG stock batch |
| `688452e` | fix(subcon): write subcon order_no as batch_no on receive |
| `4d16664` | fix(receive): write receive tag as batch_no on putaway/issue |
| `10c11ec` | feat(inventory): add identity columns to inventory_stock_movements |
| `07ce7d2` | fix(arrivals): show part in size dropdown when register_no is blank |
| `36c2776` | fix(receive): qualify topVendors columns with correct table name |
| `87cdefb` | fix(inventory): use NewSchema part accessors in receives view |
| `4828053` | fix(incoming): resolve soft-delete alias conflict in receives queries |
| `ca16a4e` | fix(incoming): restore display_part_no/name accessors on NewSchema item |
| `e0cf053` | feat(incoming): add TRIAL INVOICE xlsx import command |
| `e69eb6f` | feat(planning): add family filter + badge to MRP module |
| `cdd4e0c` | feat(planning): pivot forecast table to one row per part with month columns |
| `480a08d` | feat(planning): switch period filter to month picker + clear month labels |

---

## Arsitektur Teknis

### Domain Models (NewSchema)
- `Core`: GciPart, Vendor, VendorPart, Customer, Uom, Machine, dll.
- `Incoming`: IncomingArrival, IncomingArrivalItem, IncomingReceive.
- `Inventory`: InventoryLocationStock, InventoryStockMovement, InventoryTransfer, StockOpname.
- `Production`: ProductionOrder, ProductionGciHourlyReport, ProductionGciDowntime, Bom, BomItem.
- `Outgoing`: OutgoingDeliveryNote, OutgoingDeliveryOrder, OutgoingPo, PickingFg.

### Pola Umum
- **Stock Management**: `InventoryLocationStock::consumeStock()` dengan named arguments.
- **Traceability**: `batch_no` pada stock movement sama dengan QR tag.
- **Backflush**: konsumsi material otomatis saat output FG dicatat.

### Dependencies
- Laravel 12
- MySQL (legacy) + PostgreSQL (NewSchema target)
- QR Code generator
- Excel (import/export)
- Pusher (broadcast monitoring)

---

## Status Testing & QA

| Area | Status | Catatan |
|------|--------|---------|
| Unit Test | Partial | Belum ada test suite penuh. |
| Integration Test | Manual | Pengujian end-to-end manual. |
| QA Regression | Pass | Q-014 (incoming arrival) sudah fix. |
| UI/UX | Good | Perbaikan dropdown dan aksesibilitas. |

---

## Known Issues / Open Items

- **Testing**: Belum ada CI/CD.
- **Dokumentasi**: Endpoint API belum terdokumentasi (OpenAPI/Swagger).
- **Performance**: Query MRP dan forecasting perlu optimasi indexing.
- **Subcon**: Fitur pengembalian material masih dalam tahap perbaikan.
- **User Permissions**: Beberapa modul baru belum memiliki permission lengkap.

---

## Next Steps

1. **Penambahan Test Suite** – Unit test untuk service layer dan integrasi.
2. **Optimasi Query** – Index di `inventory_location_stock`, `production_orders`, `mrp_*`.
3. **Dokumentasi API** – Integrasi dengan Laravel Swagger atau Postman Collection.
4. **Penyempurnaan Subcon** – Selesaikan alur return dan traceability.
5. **Monitoring Real-time** – Perbaiki broadcast untuk operator board.
6. **Training User** – Siapkan user manual dan video tutorial.

---

## Kesimpulan

Project Material Incoming mencapai tahap **stabil** dengan semua modul utama berfungsi di atas NewSchema. Fix terakhir untuk traceability batch dan UI dropdown sudah selesai.

**Progress overall:** ~85%  
**Target Go-Live:** Akhir September 2026 (tentatif)

---

*Laporan dibuat berdasarkan commit history, route/controller structure, dan dokumentasi internal project.*
