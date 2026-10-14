# Panduan Migrasi & Setup Supabase - Asset Management

Dokumen ini memandu Anda langkah-demi-langkah untuk memigrasikan database aplikasi **Asset Management (PHP & Flutter WebView)** dari PostgreSQL lokal ke **Supabase** secara penuh. Karena Supabase dibangun di atas PostgreSQL, proses migrasi ini dapat dilakukan secara lancar (seamless) tanpa merusak atau mengubah query SQL yang sudah ada pada aplikasi PHP.

---

## Arsitektur Integrasi Supabase

Aplikasi PHP Anda menggunakan koneksi **PDO (PHP Data Objects)** untuk terhubung ke database. Supabase menyediakan koneksi langsung (Direct Connection) dan Connection Pooler (PgBouncer) yang sepenuhnya kompatibel dengan PDO.

```
+------------------------------+
| Aplikasi PHP / Web Server   |
+------------------------------+
               |
               v (PDO Connection)
+------------------------------------------+
| Supabase Connection Pooler (Port 6543)  |
+------------------------------------------+
               |
               v
+-----------------------------------------+
| Database PostgreSQL Supabase            |
+-----------------------------------------+
```

---

## Langkah 1: Membuat Project Baru di Supabase

1. Buka browser dan masuk ke [Supabase Dashboard](https://supabase.com/dashboard).
2. Login menggunakan akun GitHub Anda atau buat akun baru.
3. Klik tombol **New Project** dan pilih organisasi Anda.
4. Isi informasi project baru Anda:
   - **Name**: `assetmanagement`
   - **Database Password**: *Buat password yang kuat dan catat baik-baik (ini akan digunakan di file `.env`)*.
   - **Region**: Pilih region terdekat (misalnya: `Singapore - ap-southeast-1` untuk latensi terendah dari Indonesia).
   - **Pricing Plan**: Pilih **Free Tier** (Gratis).
5. Klik **Create new project** dan tunggu beberapa menit hingga database selesai disiapkan oleh Supabase.

---

## Langkah 2: Mengambil Informasi Koneksi Database (Host, Port, User)

Setelah project aktif:
1. Di sidebar kiri dashboard Supabase, klik ikon **Settings** (ikon roda gigi) di bagian bawah.
2. Pilih menu **Database**.
3. Scroll ke bawah ke bagian **Connection Info**.
4. Catat informasi berikut untuk digunakan di file `.env`:
   - **Host**: (Contoh: `aws-0-ap-southeast-1.pooler.supabase.com`)
   - **Port**: `6543` *(Gunakan port `6543` untuk Connection Pooling/Transaction mode, ini sangat direkomendasikan untuk aplikasi web berbasis PHP)*.
   - **Database Name**: `postgres` *(Default dari Supabase)*.
   - **User**: (Contoh: `postgres.your-project-reference`)
   - **Password**: *(Password yang Anda buat saat langkah 1)*.

---

## Langkah 3: Import Skema & Seed Data ke Supabase

Untuk membuat tabel-tabel dan data awal aplikasi di database Supabase:

1. Buka file `assetmanagement_pg.sql` di text editor Anda, lalu **Copy (Salin)** seluruh isi file tersebut.
2. Di dashboard Supabase Anda, buka menu **SQL Editor** dari sidebar kiri (ikon `>_`).
3. Klik **New Query** -> **Blank Query**.
4. **Paste (Tempel)** isi skema SQL yang telah disalin ke editor.
5. Klik tombol **Run** di pojok kanan bawah editor untuk mengeksekusi query.
6. Pastikan muncul pesan sukses `"Success. No rows returned"` atau sejenisnya tanpa ada error.

> **PENTING**  
> Skema `assetmanagement_pg.sql` sudah disesuaikan secara khusus untuk PostgreSQL (termasuk penyesuaian Sequence generator dan tipe data), sehingga akan berjalan sempurna di Supabase.

---

## Langkah 4: Mengonfigurasi Aplikasi PHP (.env)

Kami telah memodifikasi struktur file `includes/config.php` agar secara dinamis dapat membaca file konfigurasi `.env`.

1. Di dalam direktori root proyek (`d:\projek\assetmanagement\assetmanagement\`), buat file baru dengan nama **`.env`** (atau salin dari `.env.example`).
2. Masukkan kredensial database Supabase yang Anda peroleh dari **Langkah 2** seperti berikut:

```env
# Konfigurasi Database Supabase
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=6543
DB_USER=postgres.your-project-ref
DB_PASS=Password_Supabase_Anda
DB_NAME=postgres

# Mode Pengembangan (Ubah ke false untuk production)
DEV_MODE=false
```

> **TIPS**  
> Jika Anda ingin kembali ke database PostgreSQL lokal untuk keperluan debugging offline, cukup ubah nilai di dalam file `.env` ini kembali ke localhost, atau ubah nama file `.env` sementara (misalnya menjadi `.env.offline`). Aplikasi akan otomatis mendeteksi dan menggunakan fallback lokal.

---

## Langkah 5: Pengujian & Verifikasi Koneksi

Untuk memastikan koneksi berjalan lancar, Anda dapat menjalankan script verifikasi sederhana atau langsung membuka halaman login aplikasi.

1. Buka aplikasi via browser lokal atau emulator Android.
2. Coba login dengan kredensial default yang telah di-seed ke database Supabase Anda:
   - **Email**: `admin@demo.com`
   - **Password**: `admin123`
3. Jika berhasil masuk ke Dashboard Utama, maka migrasi Supabase Anda telah **100% Berhasil**!

---

## Penanganan File Upload (Supabase Storage - Opsional)

> **CATATAN**  
> Saat ini, file upload (seperti foto barang, file nota pembelian, dan bukti reimbursement) disimpan di direktori lokal server PHP (`public/uploads`).
> Jika di masa mendatang Anda melakukan deploy aplikasi PHP ini ke server serverless (seperti Vercel atau AWS Lambda) yang bersifat *read-only* / *ephemeral*, Anda disarankan memigrasikan penyimpanan upload ke **Supabase Storage**.
> Cara setup Supabase Storage untuk upload file:
> 1. Buat bucket baru bernama `assets` di Supabase Storage Dashboard dan set ke **Public**.
> 2. Gunakan Supabase REST API atau library HTTP client PHP untuk melakukan upload file langsung ke bucket tersebut dan simpan URL-nya ke database.
