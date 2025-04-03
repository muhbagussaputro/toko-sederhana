# Toko Sederhana

Aplikasi Toko Sederhana berbasis PHP dan MySQL yang dapat dengan mudah dijalankan di lingkungan Laragon.

## Daftar Isi
- [Fitur](#fitur)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Cara Instalasi](#cara-instalasi)
- [Penggunaan Virtual Host](#penggunaan-virtual-host)
- [Migrasi ke Laptop Lain](#migrasi-ke-laptop-lain)
- [Login Admin](#login-admin)
- [Struktur Database](#struktur-database)
- [Pembaruan Terbaru](#pembaruan-terbaru)

## Fitur

- Manajemen Barang (Tambah, Edit, Hapus, Cari)
- Manajemen Transaksi
- Laporan Penjualan
- Multi-level Login (Admin dan Kasir)
- Sistem Otentikasi
- Log Aktivitas
- Auto-setup Database
- Tool Migrasi Database antar Laptop

## Persyaratan Sistem

- Windows 7/8/10/11
- [Laragon](https://laragon.org/download/) (sudah termasuk Apache, MySQL, dan PHP)
- Web Browser Modern (Chrome, Firefox, Edge, dll)

## Cara Instalasi

### Instalasi Baru

1. **Install Laragon**
   - Download [Laragon](https://laragon.org/download/) dan install
   - Pastikan Laragon terinstall di `C:\laragon`

2. **Clone/Download Repositori**
   - Download atau clone repositori ini ke folder `C:\laragon\www\toko-sederhana`

3. **Jalankan Laragon**
   - Buka Laragon dan tekan tombol "Start All"
   - Pastikan MySQL dan Apache berjalan

4. **Akses Aplikasi**
   - Buka browser dan akses `http://localhost/toko-sederhana`
   - Sistem akan otomatis melakukan setup database saat pertama kali diakses

5. **Login**
   - Username: `admin`
   - Password: `admin123`

### Cara Upgrade

1. Backup database terlebih dahulu
2. Download versi terbaru
3. Extract ke folder `C:\laragon\www\toko-sederhana`
4. Jalankan `http://localhost/toko-sederhana/update_db.php` untuk mengupdate struktur database

## Penggunaan Virtual Host

Laragon memungkinkan Anda menggunakan virtual host untuk akses yang lebih mudah:

1. **Aktifkan Auto Virtual Hosts**
   - Buka Laragon
   - Klik Menu > Preferences > General
   - Centang "Auto create virtual hosts"
   - Klik "OK" dan restart Laragon

2. **Akses Melalui Virtual Host**
   - Aplikasi dapat diakses melalui alamat: `http://toko-sederhana.test`
   - Pastikan alamat ini sudah diatur di file hosts Windows Anda

3. **Mengatasi Masalah Virtual Host**
   - Jika terjadi redirect loop, hapus cookies browser Anda
   - Jika masih bermasalah, periksa file .htaccess di folder aplikasi

## Migrasi ke Laptop Lain

Untuk memindahkan aplikasi ini ke laptop lain, ikuti langkah-langkah berikut:

1. **Backup Database**
   - Akses `http://localhost/toko-sederhana/migrate.php`
   - Klik tombol "Backup Database Sekarang"
   - Download file backup yang dihasilkan

2. **Copy Folder Project**
   - Copy seluruh folder `toko-sederhana` di `C:\laragon\www\`

3. **Di Laptop Baru**
   - Install Laragon
   - Paste folder `toko-sederhana` ke `C:\laragon\www\`
   - Jalankan Laragon (Start All)
   - Akses `http://toko-sederhana.test`
   - Upload file backup database
   - Klik tombol "Upload & Restore Database"
   - Selesai! Aplikasi siap digunakan di laptop baru

## Login Admin

- **Username:** `admin`
- **Password:** `admin123`

## Login Kasir

- **Username:** `kasir`
- **Password:** `kasir123`

## Struktur Database

Struktur database aplikasi Toko Sederhana:

- **users** - Menyimpan data pengguna (admin, kasir)
- **barang** - Menyimpan data barang/produk
- **transaksi** - Menyimpan data transaksi
- **transaksi_detail** - Menyimpan detail item dalam transaksi
- **log_aktivitas** - Menyimpan riwayat aktivitas pengguna

## Pembaruan Terbaru

### 1.2.0 (September 2023)
- Perbaikan alur pembuatan database otomatis
- Fitur pembuatan tabel dan data default secara otomatis
- Penambahan log aktivitas yang lebih detail
- Perbaikan redirect loop pada setup database
- Navigasi UI yang lebih responsif
- Peningkatan keamanan dengan validasi input yang lebih baik

## Kontak

Jika ada pertanyaan atau menemukan masalah, silakan buat issue baru atau hubungi admin. 