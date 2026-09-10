# Daily Planning: Rombak Frontend ERP GCI

**Pemilik:** Rom
**Tanggal penyusunan:** 10 September 2026
**Scope:** Perombakan frontend lintas modul, standardisasi bahasa, validasi flow receiving sampai production, dan stabilisasi sebelum rilis.

## Ringkasan kondisi saat ini

Perubahan frontend sudah menyentuh sekitar 150 file dengan fokus pada:

- Standardisasi tampilan Blade lintas modul.
- Perbaikan label, menu, dan translation key bahasa Indonesia, Inggris, dan Korea.
- Penyesuaian halaman Arrival, Receive, Warehouse, Production, Admin, Vendor, Delivery, dan Subcon.
- Penyesuaian dashboard, launcher, role/user management, serta route production.
- Penyelarasan flow material dari penerimaan, QC, putaway, stok, sampai material issue ke production.

## Target akhir

1. Semua modul memakai layout, komponen, spacing, warna, dan pola interaksi yang konsisten.
2. Pergantian bahasa bekerja setelah navigasi, submit form, redirect, dan refresh.
3. Flow barang dapat ditelusuri dari Arrival sampai Production Issue tanpa dead end.
4. Tidak ada route, permission, translation key, atau tampilan utama yang rusak.
5. Tersedia checklist QA dan PDF handover untuk tim.

## Rencana harian

### Hari 1 — Audit baseline dan freeze scope
**Fokus:** Menetapkan kondisi awal dan prioritas.

- Catat seluruh modul dan halaman yang sudah berubah.
- Kelompokkan masalah menjadi UI, bahasa, route, permission, dan data flow.
- Tetapkan komponen bersama: layout, navbar, sidebar, breadcrumb, table, form, modal, alert, pagination.
- Tandai halaman yang masih memakai teks hardcoded.
- Tentukan halaman kritis untuk regression test.

**Output:** daftar halaman, risk register, dan prioritas perbaikan.

### Hari 2 — Fondasi layout frontend
**Fokus:** Menyatukan kerangka visual aplikasi.

- Standarisasi layout utama dan layout modul.
- Samakan sidebar, topbar, active menu, breadcrumb, dan tombol kembali.
- Samakan token warna, typography, radius, shadow, spacing, dan status badge.
- Pastikan layout responsif untuk desktop, tablet, dan layar kecil.

**Output:** fondasi layout yang dipakai semua modul.

### Hari 3 — Sistem bahasa lintas modul
**Fokus:** Membuat pergantian bahasa efektif.

- Audit middleware locale, session/cookie, route switcher, dan fallback locale.
- Audit translation key untuk `id`, `en`, dan `ko`.
- Ganti teks hardcoded pada menu, judul, tombol, tabel, form, validasi, dan notifikasi.
- Pastikan bahasa bertahan setelah redirect, submit, pagination, filter, dan refresh.
- Bersihkan cache konfigurasi, route, dan view setelah perubahan.

**Output:** language switcher konsisten di seluruh modul utama.

### Hari 4 — Modul Incoming dan receiving
**Fokus:** Merapikan proses barang masuk.

- Rapikan halaman Departure/Arrival index, create, edit, show, dan item.
- Rapikan form receive, invoice receiving, completed receive, dan label.
- Perjelas validasi vendor, invoice, qty, net weight, gross weight, container, dan tag.
- Tampilkan status proses secara konsisten: draft, received, QC, completed.
- Pastikan user memahami bahwa stok belum bertambah sebelum receiving selesai.

**Output:** flow Arrival → Receive yang jelas dan mudah digunakan.

### Hari 5 — Modul Warehouse dan stok
**Fokus:** Menyatukan operasi QC, putaway, stock, transfer, dan label.

- Rapikan halaman QC pass/hold/reject.
- Rapikan putaway single dan bulk.
- Perjelas lokasi rak, batch/tag, qty, unit, dan histori movement.
- Rapikan stock index, reconcile, adjustment, bin transfer, opname, dan label.
- Pastikan status dan pesan error dapat diterjemahkan.

**Output:** flow Receive → QC → Putaway → Stock yang dapat ditelusuri.

### Hari 6 — Modul Production
**Fokus:** Menyelesaikan alur material menuju produksi.

- Rapikan planning, material requirement, material request, WO, dan warehouse supply.
- Pastikan tombol `Post WH Supply` terhubung ke proses material issue yang benar.
- Tampilkan shortage, allocation, issue status, handover, dan start production secara jelas.
- Pastikan stok berkurang hanya satu kali saat `PRODUCTION_ISSUE`.
- Selaraskan route, permission, flash message, dan translation key.

**Output:** flow Stock → Material Request → Material Issue → Production.

### Hari 7 — Modul master data dan administrasi
**Fokus:** Menyamakan pengalaman modul pendukung.

- Rapikan Part Master, Vendor, Customer, Contract Number, Trucking, dan Subcon.
- Rapikan Admin User, Role, Permission, dan module launcher.
- Pastikan tabel, filter, export, create, edit, show, dan delete memakai pola sama.
- Periksa permission agar menu yang terlihat sesuai akses user.

**Output:** modul pendukung konsisten dengan modul utama.

### Hari 8 — Dashboard dan navigasi
**Fokus:** Membuat pengguna menemukan pekerjaan dengan cepat.

- Rapikan dashboard utama dan dashboard per modul.
- Perjelas KPI, status card, quick action, alert, dan empty state.
- Periksa semua link menu, redirect, breadcrumb, dan back navigation.
- Pastikan launcher menampilkan modul sesuai role dan locale.

**Output:** navigasi dan dashboard siap untuk penggunaan harian.

### Hari 9 — Regression dan perbaikan lintas halaman
**Fokus:** Menemukan inkonsistensi yang tersisa.

- Uji seluruh halaman kritis dalam tiga bahasa.
- Uji create, edit, delete, filter, search, pagination, export, print, dan redirect.
- Uji permission untuk role warehouse, purchasing, production, QC, admin, dan viewer.
- Catat error per halaman dengan screenshot dan langkah reproduksi.
- Perbaiki issue prioritas tinggi terlebih dahulu.

**Output:** daftar issue tersisa dengan status dan bukti perbaikan.

### Hari 10 — Finalisasi dan handover
**Fokus:** Menyiapkan rilis frontend.

- Jalankan lint, test, route check, dan cache clear.
- Review diff agar tidak ada perubahan di luar scope.
- Pastikan translation key tidak hilang dan tidak ada teks mentah yang tertinggal.
- Finalisasi checklist QA dan dokumentasi flow.
- Buat catatan known issues dan rekomendasi tahap berikutnya.

**Output:** frontend siap review user/UAT dan dokumen handover.

## Checklist acceptance

- [ ] Language switcher berlaku di semua modul utama.
- [ ] Locale tetap tersimpan setelah submit dan redirect.
- [ ] Tidak ada translation key yang tampil mentah.
- [ ] Arrival dapat dibuat dan diedit.
- [ ] Receive membuat tag dan menambah stok sesuai aturan.
- [ ] QC dan putaway menampilkan status yang benar.
- [ ] Stock movement mencatat RECEIVE, PUTAWAY, dan PRODUCTION_ISSUE.
- [ ] Material request dan material issue dapat ditelusuri ke WO.
- [ ] Tombol warehouse supply mengikuti permission yang benar.
- [ ] Print label dan export tetap berfungsi.
- [ ] Tampilan responsive dan konsisten antar modul.
- [ ] Error validation dapat dipahami user.

## Risiko dan perhatian

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Teks hardcoded masih tersebar | Bahasa tidak berubah | Audit literal string dan translation key per modul |
| Route GET tersedia tetapi action POST tidak terlihat | Flow berhenti di warehouse supply | Uji tombol dari browser dan cocokkan route form |
| QC/putaway dan receive mencatat stok ganda | Saldo stok salah | Pastikan hanya satu tahap yang melakukan update stok |
| Permission tidak sinkron dengan menu | User melihat halaman yang tidak bisa dipakai | Uji setiap role pada route dan navigation |
| Perubahan 150 file terlalu luas | Regression sulit dilacak | Gunakan checklist modul dan review diff bertahap |

## Urutan flow bisnis yang harus lolos UAT

```text
Departure / Arrival
  → Receive + Tag
  → QC Pass
  → Putaway
  → Warehouse Stock
  → Production Planning
  → Work Order
  → Material Request
  → Post WH Supply / Material Issue
  → Production Start
```

## Status dokumen

**Status:** Rencana kerja frontend dan QA lintas modul.

**Catatan:** Jadwal dapat disesuaikan berdasarkan hasil regression pada Hari 3, Hari 6, dan Hari 9.
