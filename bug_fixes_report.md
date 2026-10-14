# Laporan Bug & Perbaikan Sistem (Bug Fixes Report)

Dokumen ini mencatat bug teknis yang ditemukan selama pengembangan dan perbaikan yang telah diterapkan untuk memastikan sistem manajemen aset berjalan stabil dan profesional.

---

## 1. Bug: Kegagalan Query pada Alur Pembelian (Purchase Workflow)
- **Masalah**: Sistem mengalami crash saat menyimpan data pembelian baru. Hal ini disebabkan oleh referensi kolom `created_at` yang tidak valid dalam pernyataan `INSERT` di file `create_purchase.php`.
- **Perbaikan**: 
    - Menghapus referensi kolom yang tidak perlu yang tidak didukung oleh skema database saat ini.
    - Menambahkan mekanisme `ensureCols` untuk memastikan kolom-kolom yang dibutuhkan (`purchase_code`, `purchase_date`, dll) dibuat secara otomatis jika belum ada.
    - Menggunakan klausa `RETURNING id` (spesifik PostgreSQL) untuk mendapatkan ID transaksi secara aman.

## 2. Bug: Ketidakcocokan ID Otomatis (PostgreSQL lastInsertId)
- **Masalah**: Fungsi `lastInsertId()` sering mengembalikan nilai kosong atau error pada database PostgreSQL jika nama sequence tidak disebutkan secara eksplisit, menyebabkan data relasi (seperti mutasi stok) gagal disimpan.
- **Perbaikan**: 
    - Memperbarui panggilan `lastInsertId()` di `update_stock.php` dengan nama sequence yang eksplisit (`stock_movements_id_seq`).
    - Beralih menggunakan `fetchColumn()` setelah query dengan `RETURNING id` untuk keandalan yang lebih tinggi di lingkungan PostgreSQL.

## 3. Bug: Fragmentasi Koneksi Database
- **Masalah**: Logika koneksi database tersebar di beberapa file dengan kredensial yang berbeda-beda, menyulitkan pemeliharaan dan menyebabkan error "Connection Refused".
- **Perbaikan**: 
    - Melakukan sentralisasi logika koneksi ke dalam file `includes/db.php`.
    - Mengintegrasikan `includes/config.php` ke dalam `db.php` agar seluruh aplikasi menggunakan satu sumber kredensial yang valid.

## 4. Masalah: Tampilan Tabel Tidak Responsif pada Mobile
- **Masalah**: Tabel data yang panjang (seperti riwayat barang atau supplier) terpotong dan merusak tata letak dashboard saat dibuka di perangkat layar kecil (ponsel/tablet).
- **Perbaikan**: 
    - Membungkus seluruh elemen `<table>` dengan container `<div class="table-responsive">` di semua dashboard.
    - Menyesuaikan ukuran kolom agar lebih fleksibel menggunakan kelas utilitas Bootstrap.

## 5. Masalah: Inkonsistensi Navigasi Sidebar
- **Masalah**: Sidebar navigasi memiliki perilaku yang berbeda di tiap dashboard peran (Admin vs Atasan vs Stok), dan menu toggle tidak berfungsi pada perangkat mobile.
- **Perbaikan**: 
    - Menyatukan struktur sidebar menggunakan template `app-sidebar` yang konsisten.
    - Mengimplementasikan logika JavaScript di `assets/main.js` untuk menangani toggle sidebar, overlay, dan dropdown notifikasi secara global.

## 6. Bug: Error SQL pada Dashboard Atasan (Group By)
- **Masalah**: Query untuk statistik barang di dashboard atasan menyebabkan error "column must appear in the GROUP BY clause" karena sintaks SQL yang tidak ketat di MySQL tapi ketat di PostgreSQL.
- **Perbaikan**: 
    - Memperbaiki query SQL untuk menyertakan semua kolom non-agregat ke dalam klausa `GROUP BY`.
    - Menggunakan `COALESCE` untuk menangani nilai null pada perhitungan stok agar tidak menghasilkan error perhitungan.

---

**Status Saat Ini**: Seluruh bug di atas telah diperbaiki dan diverifikasi melalui pengujian fungsional dan teknis. Sistem kini berjalan stabil di lingkungan PostgreSQL dengan tampilan yang sepenuhnya responsif.
