# Fix Generate SO Issue - Server vs Local

## Masalah
Generate SO berfungsi di local tapi tidak di server. SO tidak muncul di Picking FG.

## Analisis Root Cause

### 1. **Migration Constraint Mismatch**
Migration `2026_02_13_100001_fix_outgoing_picking_fgs_unique_constraint.php` mengubah unique constraint dari:
- **Old:** `[delivery_date, gci_part_id, source]`
- **New:** `[delivery_date, gci_part_id, sales_order_id]`

**Kemungkinan di server:**
- Migration belum dijalankan
- Constraint masih menggunakan versi lama
- Error: Duplicate entry saat insert picking FG

### 2. **Solusi yang Sudah Diterapkan**

#### A. Enhanced Error Handling & Logging
File: `app/Http/Controllers/OutgoingController.php`

**Added:**
- Try-catch wrapper untuk keseluruhan method
- Validasi customer & GCI part di awal
- Logging detail untuk setiap step:
  - Transaction start
  - Planning line not found
  - SO creation/update
  - Picking FG creation/update
  - Exception details dengan line number

**Log akan muncul di:** `storage/logs/laravel.log`

#### B. Better Validation
```php
// Validate customers exist
$existingCustomers = Customer::whereIn('id', $customerIds)->pluck('id');
$invalidCustomers = $customerIds->diff($existingCustomers);

// Validate GCI parts exist
$existingParts = GciPart::whereIn('id', $partIds)->pluck('id');
$invalidParts = $partIds->diff($existingParts);
```

## Langkah Troubleshooting di Server

### Step 1: Cek Migration Status
```bash
ssh ke-server
cd /path/to/project
php artisan migrate:status
```

**Cari migration:** `2026_02_13_100001_fix_outgoing_picking_fgs_unique_constraint`

**Jika belum running:**
```bash
php artisan migrate
```

### Step 2: Cek Unique Constraint
```sql
-- MySQL
SHOW INDEX FROM outgoing_picking_fgs;

-- PostgreSQL
SELECT indexname, indexdef
FROM pg_indexes
WHERE tablename = 'outgoing_picking_fgs';
```

**Expected:** Index bernama `opf_date_part_so_unique` dengan kolom `[delivery_date, gci_part_id, sales_order_id]`

### Step 3: Cek Log Error
```bash
tail -f storage/logs/laravel.log
```

**Lakukan Generate SO di browser**, kemudian lihat log untuk:
- "Generate SO Transaction Start"
- "Creating new SO"
- "Created Picking FG"
- Error messages dengan detail

### Step 4: Cek Data Existing
```sql
-- Cek apakah ada picking FG dengan sales_order_id NULL yang bisa konflik
SELECT * FROM outgoing_picking_fgs
WHERE delivery_date = '2026-02-14'
AND sales_order_id IS NULL;

-- Cek constraint aktif
SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_NAME = 'outgoing_picking_fgs';
```

## Potential Fixes

### Fix 1: Run Migration (Recommended)
```bash
php artisan migrate
```

### Fix 2: Manual Constraint Fix (If migration fails)
```sql
-- Drop old constraint
ALTER TABLE outgoing_picking_fgs DROP INDEX opf_date_part_source_unique;

-- Add new constraint
ALTER TABLE outgoing_picking_fgs ADD UNIQUE KEY opf_date_part_so_unique (delivery_date, gci_part_id, sales_order_id);
```

### Fix 3: Clear Old Data (If duplicates exist)
```sql
-- Backup first!
-- Delete orphaned picking FGs without sales_order_id
DELETE FROM outgoing_picking_fgs
WHERE sales_order_id IS NULL
AND delivery_date < CURDATE();
```

### Fix 4: Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

## Verification Steps

After applying fix:

1. **Test Generate SO:**
   - Buka Delivery Plan page
   - Pilih date dengan data
   - Input trip quantity
   - Klik "Generate SO"
   - Check success message

2. **Verify SO Created:**
   ```sql
   SELECT * FROM sales_orders
   WHERE so_date = '2026-02-14'
   ORDER BY id DESC
   LIMIT 10;
   ```

3. **Verify Picking FG Created:**
   ```sql
   SELECT * FROM outgoing_picking_fgs
   WHERE delivery_date = '2026-02-14'
   ORDER BY id DESC
   LIMIT 10;
   ```

4. **Check Picking FG Page:**
   - Buka `/outgoing/picking-fg?date=2026-02-14`
   - SO harus muncul dengan status "pending"

## Error Messages Reference

| Error Message | Cause | Solution |
|--------------|-------|----------|
| `Duplicate entry for key 'opf_date_part_source_unique'` | Old constraint masih aktif | Run migration atau manual drop constraint |
| `Pilih minimal 1 part untuk generate SO` | No lines selected | Normal validation |
| `Ada customer yang tidak valid` | Customer ID tidak ditemukan | Cek data customer |
| `Ada GCI part yang tidak valid` | GCI Part ID tidak ditemukan | Cek data GCI parts |
| `Planning line not found` (in log) | No delivery planning line for date | Normal, skip this part |

## Files Modified

1. `app/Http/Controllers/OutgoingController.php`
   - Added comprehensive logging
   - Added validation before transaction
   - Added try-catch error handling

## Contact & Support

**Modified by:** Claude AI
**Date:** 2026-02-14
**Purpose:** Debug generate SO issue on server
