# Spesifikasi Modul: Daily Jig Planning

## 1. Overview
Modul "Daily Jig Planning" berfungsi untuk merencanakan dan melacak penggunaan Jig per hari berdasarkan "Line" dan "Case". Modul ini terintegrasi dengan Modul Outgoing dan memiliki kemiripan struktur dengan "Daily Planning" (Production), namun berfokus pada kuantitas Jig.

## 2. Struktur Database
Untuk mengakomodasi data ini, kita akan membuat struktur tabel baru yang terpisah namun mirip dengan Daily Plan untuk performa dan fleksibilitas.

### ERD Schema
1.  **`outgoing_jig_plans`**
    *   `id`: PK
    *   `period_month`: string (YYYY-MM) - Menyimpan periode perencanaan (opsional, bisa juga date range seperti DailyPlan)
    *   `date_from`: date
    *   `date_to`: date
    *   `created_by`: fk users
    *   `timestamps`

2.  **`outgoing_jig_plan_rows`**
    *   `id`: PK
    *   `plan_id`: FK `outgoing_jig_plans`
    *   `line`: string (Disimpan sesuai upload, e.g. "Line 1")
    *   `case_name`: string (Disimpan sesuai upload, e.g. "Case A")
    *   `customer_part_id`: FK `customer_parts` (nullable, resolved via Line + Case)
    *   `timestamps`

3.  **`outgoing_jig_plan_cells`**
    *   `id`: PK
    *   `row_id`: FK `outgoing_jig_plan_rows`
    *   `plan_date`: date
    *   `jig_qty`: integer (Jumlah Jig)
    *   `timestamps`

## 3. Spesifikasi Format Data (Excel / Upload)
Format file upload adalah Excel (`.xlsx`, `.xls`) atau CSV.

### Header Format
Baris pertama (Row 1) berisi header kolom.

| No | LINE | Case | 2026-01-28 Jig | 2026-01-29 Jig | ... |
| -- | ---- | ---- | -------------- | -------------- | --- |
| 1  | L1   | C123 | 5              | 6              | ... |

*   **No**: Nomor urut (Optional, diabaikan saat import).
*   **LINE**: Identitas Line produksi.
*   **Case**: Identitas Case (akan dicocokkan dengan `customer_parts.case_name`).
*   **YYYY-MM-DD Jig**: Kolom dinamis berisi tanggal. Format tanggal harus bisa diparsing (e.g., `YYYY-MM-DD`). Suffix " Jig" bersifat opsional tapi direkomendasikan untuk kejelasan.

## 4. Endpoints & Routing

### Web Routes (`routes/web.php`)
Prefix: `/outgoing/jig-planning`
Name Prefix: `outgoing.jig-planning.`

1.  **View & List**
    *   **Method**: `GET`
    *   **URL**: `/outgoing/jig-planning`
    *   **Controller**: `OutgoingJigPlanningController@index`
    *   **Deskripsi**: Menampilkan tabel Jig Planning dengan filter Date Range, Line, dan Case.

2.  **Import Data**
    *   **Method**: `POST`
    *   **URL**: `/outgoing/jig-planning/import`
    *   **Controller**: `OutgoingJigPlanningController@import`
    *   **Parameter**: `file` (Multipart form-data)
    *   **Deskripsi**: Memproses file Excel dan menyimpan data ke database.

3.  **Download Template**
    *   **Method**: `GET`
    *   **URL**: `/outgoing/jig-planning/template`
    *   **Controller**: `OutgoingJigPlanningController@template`
    *   **Query Params**: `date_from`, `date_to`
    *   **Deskripsi**: Menghasilkan file Excel template kosong dengan header tanggal yang dinamis sesuai range yang diminta.

## 5. Proses Validasi (Business Logic)

### Level 1: Request Validation
*   File harus bertipe: `xlsx`, `xls`, `csv`.
*   Maksimal ukuran file: 2MB (configurable).

### Level 2: Header Validation (Saat Import)
*   Sistem membaca baris header.
*   Wajib ada kolom: `LINE` (atau alias `Line`), `Case` (atau alias `CASE`).
*   Sistem mencari kolom-kolom tanggal. Minimal ditemukan 1 kolom tanggal yang valid.
    *   Regex Tanggal: Mampu membaca format `YYYY-MM-DD` atau `DD-MM-YYYY`, dengan atau tanpa suffix " Jig".

### Level 3: Row Data Validation
*   **Line & Case**: Tidak boleh kosong.
*   **Check Duplicates**: Kombinasi `LINE` + `Case` dalam satu file yang sama akan dianggap sebagai baris yang berbeda (atau digabung, tergantung policy, default: digabung/sum qty).
*   **Data Resolution**:
    *   Sistem mencoba mencari `customer_parts` berdasarkan `line` & `case_name`.
    *   Jika ditemukan -> Simpan `customer_part_id`.
    *   Jika tidak ditemukan -> Tetap simpan sebagai text mentah, beri notifikasi "Unmapped Case" tapi **jangan gagalkan import** (Soft Validasi).
*   **Jig Qty**:
    *   Harus numeric integer.
    *   Jika kosong atau `-`, dianggap 0.

## 6. Feedback ke User
*   **Success**: "Import berhasil. [X] baris data diproses."
*   **Partial Success**: "Import berhasil. [X] baris data diproses. Peringatan: [Y] Case tidak ditemukan di database."
*   **Error**: "Format file tidak valid. Kolom 'LINE' atau 'Case' tidak ditemukan."
