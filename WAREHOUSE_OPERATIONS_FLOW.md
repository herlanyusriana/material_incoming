# Dokumentasi Warehouse Operations Flow

## Overview

Sistem Warehouse Operations mengelola alur material dari kedatangan sampai penyimpanan dan pergerakan di warehouse. Flow utama terdiri dari beberapa tahapan:

1. **QC (Quality Check)** - Inspeksi material yang datang
2. **Putaway** - Penyimpanan material ke lokasi warehouse
3. **Bin Transfer** - Perpindahan material antar lokasi atau batch
4. **Stock Management** - Monitoring dan rekonsiliasi stock
5. **Stock Adjustment** - Adjustment manual (restricted)
6. **Production Load** - View jadwal produksi untuk persiapan warehouse
7. **Barcode Labels** - Print label untuk part dan lokasi

**Permission Required**: Semua warehouse operations memerlukan permission `manage_warehouse` ([routes/web.php:188](routes/web.php#L188))

---

## 1. QC Operations (Quality Check)

**Controller**: [WarehouseQcController.php](app/Http/Controllers/WarehouseQcController.php)

### Functions

#### `index(Request $request)`
**Purpose**: Menampilkan daftar material yang perlu QC (status: hold, reject, fail)

**Query**:
```php
IncomingReceive::query()
    ->with(['incomingArrivalItem.gciPart', 'incomingArrivalItem.incomingArrival.vendor', 'qcUpdater'])
    ->whereIn(DB::raw('LOWER(qc_status)'), ['hold', 'reject', 'fail'])
    ->when($status !== '', fn ($q) => $q->whereRaw('LOWER(qc_status) = ?', [$status]))
    ->when($search !== '', function ($q) use ($search) {
        // Search by tag, arrival_no, part_no
    })
    ->latest()
    ->paginate($perPage)
```

**Parameters**:
- `search` - Search tag, arrival number, part number
- `status` - Filter by QC status (hold/reject/fail)
- `per_page` - Pagination (default: 50, min: 10, max: 200)

**View**: `warehouse.qc.index`

---

#### `update(Request $request, IncomingReceive $receive)`
**Purpose**: Update QC status material (pass/hold/reject)

**Validation**:
- `qc_status` - required, in: ['pass', 'hold', 'reject']
- `qc_note` - optional, max 2000 chars

**Query**:
```php
$receive->update([
    'qc_status' => $newStatus,
    'qc_note' => $note,
    'qc_updated_at' => now(),
    'qc_updated_by' => auth()->user()->id,
]);
```

**Flow**:
1. Validate input (status harus pass/hold/reject)
2. Update receive record dengan QC status baru
3. Jika status = 'pass', redirect ke Putaway queue
4. Return dengan success message

---

## 2. Putaway Operations

**Controller**: [WarehousePutawayController.php](app/Http/Controllers/WarehousePutawayController.php)

### Functions

#### `index(Request $request)`
**Purpose**: Menampilkan daftar material yang sudah QC pass dan siap di-putaway

**Query**:
```php
Receive::query()
    ->with(['arrivalItem.vendorPart', 'arrivalItem.gciPart', 'arrivalItem.arrival.vendor'])
    ->where('qc_status', 'pass')
    ->where(function ($q) {
        $q->whereNull('location_code')->orWhere('location_code', '');
    })
    ->latest()
    ->paginate($perPage)
```

**Kondisi**: Material harus:
- QC status = 'pass'
- location_code masih kosong (belum di-putaway)

**View**: `warehouse.putaway.index`

---

#### `store(Request $request, Receive $receive)`
**Purpose**: Assign material ke warehouse location (single putaway)

**Validation**:
- `location_code` - required, exists in `warehouse_locations` (status: active)
- `putaway_date` - optional date

**Flow**:
```
1. Validate QC status = 'pass'
2. Resolve gci_part_id (from arrivalItem atau vendorPart)
3. Hitung qty contribution:
   - Jika unit = 'COIL' → gunakan net_weight
   - Selain itu → gunakan qty
4. START TRANSACTION
   a. Lock receive record (lockForUpdate)
   b. Jika old location ada dan berbeda:
      - Kurangi stock dari old location
   c. Tambah stock ke new location
   d. Update receive.location_code
5. COMMIT
6. Log activity
```

**Query Update Stock**:
```php
InventoryLocationStock::updateStock(
    $gciPartId,
    $locationCode,
    $qtyContribution,  // positive untuk tambah, negative untuk kurang
    null,              // batch_no
    $receive->tag,
    'PUTAWAY',
    "RCV#{$receive->id}",
    $receive->id,
    // ... other params
)
```

**Issues Found**:
⚠️ **Potential Race Condition**: Tidak ada validasi jika location_code sudah terisi saat user submit. Jika 2 user putaway simultaneously, bisa double-update.

---

#### `bulk(Request $request)`
**Purpose**: Bulk putaway multiple receives ke satu lokasi

**Validation**:
- `location_code` - required, exists in warehouse_locations
- `putaway_date` - optional
- `receive_ids` - required array of integers

**Flow**:
```
1. START TRANSACTION
2. Lock semua receive records (whereIn + lockForUpdate)
3. Loop each receive:
   a. Skip jika qc_status bukan 'pass'
   b. Skip jika location_code sudah ada
   c. Skip jika gci_part_id null
   d. Skip jika qty <= 0
   e. Update stock + location_code
4. COMMIT
5. Return summary (updated count + skipped count)
```

---

#### `destroy(Receive $receive)`
**Purpose**: Hapus receive dari putaway queue (sebelum di-putaway)

**Validation**:
- Receive harus belum punya location_code

**Flow**:
```
1. Validate location_code harus kosong
2. START TRANSACTION
   a. Jika sudah di-putaway (location_code ada):
      - Kurangi stock dari location
   b. Delete receive record
3. COMMIT
```

**Issues Found**:
⚠️ **Logic Bug di line 259-260**: Check `!empty($receive->location_code)` di awal function return error, tapi di dalam transaction line 270 masih ada check lagi. Logic duplikat dan inconsistent.

---

## 3. Bin Transfer Operations

**Controller**: [BinTransferController.php](app/Http/Controllers/BinTransferController.php)

### Modes
Controller ini support 2 mode:
- **bin_to_bin**: Transfer material antar lokasi warehouse
- **batch_to_batch**: Transfer material antar batch dalam satu lokasi

Mode ditentukan dari route default: `defaults('mode', 'bin_to_bin')`

### Functions

#### `index(Request $request)`
**Purpose**: History transfer (bin-to-bin atau batch-to-batch)

**Query**:
```php
BinTransfer::with(['part', 'gciPart', 'fromLocation', 'toLocation', 'creator'])
    ->when($mode === 'bin_to_bin', function ($q) {
        $q->where(function ($qq) {
            $qq->whereNull('transfer_type')->orWhere('transfer_type', 'bin_to_bin');
        });
    })
    ->when($mode === 'batch_to_batch', fn ($q) => $q->where('transfer_type', 'batch_to_batch'))
    ->orderBy('transfer_date', 'desc')
    ->paginate(50)
```

**Filters**:
- `part_id` - Filter by part
- `location` - Filter by from/to location
- `date_from`, `date_to` - Filter by transfer date range

**View**: `warehouse.bin-transfers.index`

---

#### `store(Request $request)`
**Purpose**: Execute bin transfer (bin-to-bin atau batch-to-batch)

**Validation (bin_to_bin)**:
- `part_id` - required
- `qty` - required, numeric, min: 0.0001
- `transfer_date` - required date
- `from_location_code` - required, exists (status: ACTIVE)
- `to_location_code` - required, different from `from_location_code`
- `notes` - optional, max 1000 chars

**Validation (batch_to_batch)**:
- `part_id` - required
- `qty` - required, numeric, min: 0.0001
- `transfer_date` - required date
- `location_code` - required, exists (status: ACTIVE)
- `from_batch_no` - required, max 255
- `to_batch_no` - required, max 255, different from `from_batch_no`
- `notes` - optional

**Flow (bin_to_bin)**:
```
1. Resolve part_id dan gci_part_id
2. Check stock di source location
3. Validate stock >= qty transfer
4. START TRANSACTION
   a. Kurangi stock dari from_location
   b. Tambah stock ke to_location
   c. Create BinTransfer record
   d. Create LocationInventoryAdjustment record
5. COMMIT
```

**Flow (batch_to_batch)**:
```
1. Resolve part_id dan gci_part_id
2. Check stock di source batch
3. Validate stock >= qty transfer
4. START TRANSACTION
   a. Kurangi stock dari from_batch
   b. Tambah stock ke to_batch (same location)
   c. Create BinTransfer record
   d. Create LocationInventoryAdjustment record
5. COMMIT
```

**Issues Found**:
🔴 **CRITICAL - Missing Namespace Imports**: 
- Line 80, 81: `Part::orderBy()` dan `WarehouseLocation::where()` - Missing `use` statements
- Line 136-137, 207, 318, 347: References to `Part`, `LocationInventory`, `LocationInventoryAdjustment` tanpa full namespace

**Expected Imports**:
```php
use App\Models\Part;  // atau path yang sesuai
use App\Models\LocationInventory;
use App\Models\LocationInventoryAdjustment;
```

---

#### API Endpoints

**`getLocationStock(Request $request)`**
Get current stock di lokasi tertentu untuk part tertentu.

**`getPartLocations(Request $request)`**
Get semua lokasi yang punya stock untuk part tertentu.

**`getLocationBatches(Request $request)`**
Get semua batch di lokasi tertentu untuk part tertentu.

---

## 4. Warehouse Stock Management

**Controller**: [WarehouseStockController.php](app/Http/Controllers/WarehouseStockController.php)

### Functions

#### `index(Request $request)`
**Purpose**: View stock by location

**Query**:
```php
InventoryLocationStock::query()
    ->with(['gciPart'])
    ->when($onlyPositive, fn ($q) => $q->where('qty_on_hand', '>', 0))
    ->when($location !== '', fn ($q) => $q->where('location_code', $location))
    ->when(classification in ['RM', 'WIP', 'FG'], 
        fn ($q) => $q->whereHas('gciPart', fn ($qg) => $qg->where('classification', $classification)))
    ->orderBy('location_code')
    ->orderBy('gci_part_id')
    ->paginate($perPage)
```

**Aggregate Query**:
```php
InventoryLocationStock::query()
    ->selectRaw('location_code, SUM(qty_on_hand) as total_qty')
    ->groupBy('location_code')
    ->pluck('total_qty', 'location_code')
```

**Filters**:
- `search` - Part number, part name, location code
- `location` - Filter by specific location
- `classification` - RM/WIP/FG
- `only_positive` - Show only positive stock (default: true)

---

#### `reconcile(Request $request)`
**Purpose**: Reconcile stock antara location stock dan summary inventory

**Query**:
```php
// Aggregate per gci_part
$locationSums = InventoryLocationStock::query()
    ->whereNotNull('gci_part_id')
    ->selectRaw('gci_part_id, SUM(qty_on_hand) as loc_qty')
    ->groupBy('gci_part_id')
    ->pluck('loc_qty', 'gci_part_id')
```

**Logic**:
```php
foreach (GciPart::with(['customers'])->cursor() as $gciPart) {
    $locQty = $locationSums[$gciPart->id] ?? 0;
    $onHand = $locQty;  // Simplified: now same
    $diffQty = $onHand - $locQty;  // Always 0
}
```

**Issues Found**:
⚠️ **Logic Issue (line 99-101)**: 
```php
$locQty = (float) ($locationSums[$gciPart->id] ?? 0);
$onHand = $locQty;
$diffQty = $onHand - $locQty; // Always 0!
```
Reconcile function always produces `diff_qty = 0` karena `onHand` dan `locQty` adalah nilai yang sama. Seharusnya `onHand` diambil dari source lain (global inventory summary atau table terpisah).

---

#### `importLocationStock(Request $request)`
Import stock dari Excel/CSV file.

#### `export(Request $request)`
Export stock ke Excel dengan filters yang sama seperti index.

---

## 5. Stock Adjustment

**Controller**: [WarehouseStockAdjustmentController.php](app/Http/Controllers/WarehouseStockAdjustmentController.php)

### Authorization
⚠️ **Restricted**: Hanya role `admin` dan `ppic` yang bisa melakukan stock adjustment (line 17).

```php
private const AUTHORITY_ROLES = ['admin', 'ppic'];
```

### Event Types
```php
private const EVENT_TYPES = [
    'stock_opname',
    'audit_correction',
    'system_posting_fix',
    'damage_loss_confirmation',
    'month_end_cutoff',
];
```

### Functions

#### `index(Request $request)`
**Purpose**: View history stock adjustments

**Query**:
```php
LocationInventoryAdjustment::query()
    ->with(['part', 'gciPart', 'location', 'creator'])
    ->when($location !== '', /* filter by location */)
    ->when($search !== '', /* search part/location */)
    ->when($dateFrom, fn ($q) => $q->whereDate('adjusted_at', '>=', $dateFrom))
    ->when($dateTo, fn ($q) => $q->whereDate('adjusted_at', '<=', $dateTo))
    ->orderByDesc('adjusted_at')
    ->paginate($perPage)
```

---

#### `store(Request $request)`
**Purpose**: Create stock adjustment (set qty_after manually)

**Validation**:
- `part_id` - required (bisa dari parts atau gci_parts)
- `event_type` - required, in EVENT_TYPES
- `location_code` - required, exists (status: ACTIVE)
- `batch_no` - optional
- `qty_after` - required, numeric, min: 0
- `adjusted_at` - optional date
- `reason` - required, max 1000 chars

**Flow (with batch_no)**:
```
1. Find/create LocationInventory record for batch
2. Lock record (lockForUpdate)
3. Calculate qty_change = qty_after - qty_before
4. Update qty_on_hand = qty_after
5. Create LocationInventoryAdjustment record
6. Update last_counted_at
```

**Flow (without batch_no)**:
```
1. Get all LocationInventory records for location+part
2. Lock all records
3. Calculate total qty_before
4. Calculate qty_change = qty_after - qty_before
5. Distribute qty_after across batches (FIFO by production_date)
6. Create LocationInventoryAdjustment record
```

**Issues Found**:
🔴 **Missing Model**: Line 142-148 references `Part` dan `GciPart` tanpa use statement yang jelas.

---

#### `getBatches(Request $request)` (API)
**Purpose**: Get available batches for part+location selection

**Query**:
```php
LocationInventory::query()
    ->where(function($q) use ($partId) {
        $q->where('part_id', $partId)->orWhere('gci_part_id', $partId);
    })
    ->where('location_code', $locationCode)
    ->where('qty_on_hand', '>', 0)
    ->orderBy('production_date')
    ->orderBy('batch_no')
    ->limit($limit)
    ->get(['batch_no', 'qty_on_hand', 'production_date'])
```

---

## 6. Production Load

**Controller**: [WarehouseProductionLoadController.php](app/Http/Controllers/WarehouseProductionLoadController.php)

### Functions

#### `index(Request $request)`
**Purpose**: View production schedule untuk warehouse planning

**Query**:
```php
ProductionOrder::query()
    ->with('part')
    ->whereBetween('plan_date', [$from, $to])
    ->when($search !== '', /* search PO number / part */)
    ->when($status !== '', fn ($q) => $q->where('status', $status))
    ->orderBy('plan_date')
    ->orderBy('production_order_number')
    ->get()
```

**Default Range**: Today + 6 days

**Aggregate**:
```php
$totalsByDate = $orders
    ->groupBy(fn ($o) => $o->plan_date)
    ->map(fn ($group) => $group->sum('qty_planned'))
```

**View**: `warehouse.production_load`

---

## 7. Barcode Labels

**Controller**: [BarcodeLabelController.php](app/Http/Controllers/BarcodeLabelController.php)

### Functions

#### `printPartLabel(GciPart $part, Request $request)`
Generate label untuk part dengan barcode dan QR code.

**Barcode Format**: CODE 128
**QR Payload**: Simple barcode string

#### `printLineStockLabel(GciPart $part, Request $request)`
Generate label untuk line stock dengan QR code.

**QR Payload**:
```json
{
  "type": "LINE_STOCK",
  "gci_part_id": 123,
  "part_no": "ABC-123",
  "location": "LINE-STOCK",
  "policy": "backflush_line_stock"
}
```

#### `printBulkLabels(Request $request)`
Print multiple labels sekaligus.

#### `index(Request $request)`
Interface untuk select part dan print labels.

---

## Complete Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                  INCOMING MATERIAL ARRIVES                  │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  1. QC OPERATIONS (WarehouseQcController)                   │
│  ─────────────────────────────────────────                  │
│  • Material dengan status: hold/reject/fail                 │
│  • QC Inspector melakukan inspection                        │
│  • Update status ke: pass / hold / reject                   │
└────────────────────────┬────────────────────────────────────┘
                         │
                         │ QC Status = PASS
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  2. PUTAWAY OPERATIONS (WarehousePutawayController)         │
│  ──────────────────────────────────────────────             │
│  • Material yang sudah QC pass masuk queue                  │
│  • location_code masih kosong                               │
│  • Assign location_code                                     │
│  • Update InventoryLocationStock (+qty)                     │
└────────────────────────┬────────────────────────────────────┘
                         │
                         │ Material tersimpan di warehouse
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  3. STOCK OPERATIONS                                        │
│  ────────────────────                                       │
│  ┌──────────────────────────────────────────────┐          │
│  │ A. View Stock (WarehouseStockController)     │          │
│  │    • By location, classification             │          │
│  │    • Export/Import                            │          │
│  └──────────────────────────────────────────────┘          │
│                                                              │
│  ┌──────────────────────────────────────────────┐          │
│  │ B. Reconcile Stock                            │          │
│  │    • Compare location vs summary              │          │
│  │    • (Currently has logic issue)              │          │
│  └──────────────────────────────────────────────┘          │
└─────────────────────────────────────────────────────────────┘
                         │
                         │ Perlu pindah lokasi?
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  4. BIN TRANSFER (BinTransferController)                    │
│  ─────────────────────────────────────                      │
│  ┌──────────────────────────────────────────────┐          │
│  │ A. Bin to Bin Transfer                        │          │
│  │    • Pindah antar warehouse location          │          │
│  │    • Update stock: -from_location, +to_location│         │
│  └──────────────────────────────────────────────┘          │
│                                                              │
│  ┌──────────────────────────────────────────────┐          │
│  │ B. Batch to Batch Transfer                    │          │
│  │    • Pindah antar batch (same location)       │          │
│  │    • Update stock: -from_batch, +to_batch     │          │
│  └──────────────────────────────────────────────┘          │
└─────────────────────────────────────────────────────────────┘
                         │
                         │ Perlu adjustment?
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  5. STOCK ADJUSTMENT (WarehouseStockAdjustmentController)   │
│  ───────────────────────────────────────────────────        │
│  • RESTRICTED: Admin/PPIC only                              │
│  • Manual set qty_after                                     │
│  • Event types: stock_opname, audit, fix, damage, cutoff   │
│  • Create adjustment record                                 │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  SUPPORTING OPERATIONS                                      │
│  ────────────────────────                                   │
│  • Production Load View (schedule planning)                 │
│  • Barcode Label Printing (part labels, line stock labels) │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Tables & Models

### Primary Tables
- `incoming_receives` - Receive records dari incoming material
- `incoming_arrival_items` - Items dari arrival
- `inventory_location_stocks` - Stock per location per part
- `inventory_stock_movements` - Movement history
- `warehouse_locations` - Master lokasi warehouse
- `bin_transfers` - Bin/batch transfer history
- `location_inventory_adjustments` - Stock adjustment history
- `gci_parts` - Master part (GCI)
- `parts` - Vendor parts
- `vendor_parts` - Link vendor-gci parts

### Key Relationships
```
IncomingReceive
  → arrivalItem (BelongsTo IncomingArrivalItem)
    → gciPart (BelongsTo GciPart)
    → vendorPart (BelongsTo VendorPart)
    → arrival (BelongsTo IncomingArrival)
      → vendor (BelongsTo Vendor)

InventoryLocationStock
  → gciPart (BelongsTo GciPart)

BinTransfer
  → part (BelongsTo Part)
  → gciPart (BelongsTo GciPart)
  → fromLocation (BelongsTo WarehouseLocation)
  → toLocation (BelongsTo WarehouseLocation)
  → creator (BelongsTo User)
```

---

## Summary of Issues Found

### 🔴 Critical Issues

1. **[BinTransferController.php]** Missing namespace imports
   - Lines: 80, 81, 136-137, 207, 318, 347
   - Missing: `use App\Models\Part`, `use App\Models\LocationInventory`, `use App\Models\LocationInventoryAdjustment`
   - Impact: Runtime error saat class tidak ditemukan

2. **[WarehouseStockAdjustmentController.php]** Missing model references
   - Lines: 142-148
   - Missing clear namespace untuk `Part` dan `GciPart`

### ⚠️ Logic Issues

3. **[WarehouseStockController.php:99-101]** Reconcile always returns 0 diff
   - `$diffQty = $onHand - $locQty` where `$onHand = $locQty`
   - Reconcile function tidak berguna karena selalu 0
   - Fix: `$onHand` harus dari source berbeda (global inventory summary)

4. **[WarehousePutawayController.php:259-270]** Duplicate validation logic
   - Line 259: Check `!empty($receive->location_code)` return error
   - Line 270: Check lagi dalam transaction
   - Logic redundant dan membingungkan

5. **[WarehousePutawayController.php:111-117]** Potential race condition
   - Tidak ada validasi jika location sudah terisi saat concurrent putaway
   - Bisa double-update jika 2 user putaway simultaneously

### ℹ️ Design Considerations

6. **[WarehouseStockAdjustmentController.php:17]** Hardcoded authority roles
   - `AUTHORITY_ROLES = ['admin', 'ppic']`
   - Better: Use permission-based authorization via `manage_warehouse` atau separate `manage_stock_adjustment`
   - Current implementation bypass Laravel's permission system

7. **Missing transaction isolation levels**
   - Semua DB::transaction() menggunakan default isolation level
   - Untuk concurrent operations (putaway, transfer), consider `SERIALIZABLE` or explicit locks

---

## Recommendations

### High Priority
1. ✅ Fix missing namespace imports di BinTransferController
2. ✅ Fix reconcile logic di WarehouseStockController
3. ✅ Add validation untuk prevent double putaway

### Medium Priority
4. Migrate hardcoded roles ke permission-based authorization
5. Add explicit transaction isolation levels untuk critical operations
6. Add audit trail untuk semua stock movements (sudah partial via LogsActivity trait)

### Low Priority
7. Optimize queries dengan eager loading untuk reduce N+1
8. Add indexes untuk frequently queried columns (location_code, qc_status, transfer_date)
9. Consider caching untuk location list dan part master data

---

## Testing Checklist

Sebelum production deployment, test scenarios berikut:

### QC Operations
- [ ] Filter by status (hold/reject/fail)
- [ ] Search by tag, arrival number, part number
- [ ] Update QC status (pass/hold/reject)
- [ ] Verify redirect ke putaway setelah pass

### Putaway Operations
- [ ] Single putaway dengan lokasi baru
- [ ] Update putaway (pindah lokasi)
- [ ] Bulk putaway multiple receives
- [ ] Verify stock update di InventoryLocationStock
- [ ] Test concurrent putaway (2 users simultaneously)

### Bin Transfer
- [ ] Bin-to-bin transfer dengan stock validation
- [ ] Batch-to-batch transfer dalam satu lokasi
- [ ] Insufficient stock scenario (should fail)
- [ ] Print transfer label

### Stock Management
- [ ] View stock by location dengan filters
- [ ] Export stock to Excel
- [ ] Import stock dari Excel
- [ ] Reconcile stock (after fixing logic issue)

### Stock Adjustment
- [ ] Verify authorization (admin/ppic only)
- [ ] Adjustment dengan batch specified
- [ ] Adjustment tanpa batch (distribute across batches)
- [ ] All event types

### Production Load
- [ ] View production schedule dengan date range
- [ ] Filter by status and search

### Barcode Labels
- [ ] Print single part label
- [ ] Print line stock label
- [ ] Print bulk labels

---

## Monitoring & Maintenance

### Queries to Monitor
```sql
-- Stock discrepancies
SELECT 
  gci_part_id,
  location_code,
  qty_on_hand,
  last_counted_at
FROM inventory_location_stocks
WHERE qty_on_hand < 0;

-- Pending putaway (QC pass but no location)
SELECT COUNT(*) 
FROM incoming_receives 
WHERE qc_status = 'pass' 
  AND (location_code IS NULL OR location_code = '');

-- Recent stock adjustments
SELECT * 
FROM location_inventory_adjustments 
WHERE adjusted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY adjusted_at DESC;

-- Bin transfer activity
SELECT 
  DATE(transfer_date) as date,
  COUNT(*) as transfer_count,
  SUM(qty) as total_qty
FROM bin_transfers
WHERE transfer_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(transfer_date);
```

---

**Document Generated**: 2026-08-24  
**Version**: 1.0  
**Author**: System Documentation
