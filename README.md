# Sistem Pendukung Keputusan Penilaian Karyawan

Aplikasi web ini digunakan untuk membantu proses penilaian dan pemeringkatan karyawan menggunakan metode AHP (Analytical Hierarchy Process) dan Hybrid. Sistem ini memudahkan HRD atau manajer dalam mengambil keputusan berbasis data yang objektif dan terstruktur.

## Fitur Utama
- Manajemen data karyawan
- Manajemen kriteria penilaian
- Perhitungan ranking karyawan dengan metode AHP dan Hybrid
- Tampilan hasil ranking secara otomatis

## Kebutuhan Sistem
- Web server (disarankan: XAMPP, Laragon, atau sejenisnya)
- PHP 7.x atau lebih baru
- MySQL/MariaDB

## Cara Instalasi & Menjalankan di Lokal

1. **Clone atau Download Repository**
   - Clone repository ini atau download file ZIP, lalu ekstrak ke folder web server Anda (misal: `htdocs` di XAMPP atau `www` di Laragon).

2. **Import Database**
   - Buka phpMyAdmin atau tool database Anda.
   - Buat database baru, misal: `sistem_penilaian`.
   - Import file `sistem_penilaian.sql` yang ada di dalam folder project ke database tersebut.

3. **Konfigurasi Koneksi Database**
   - Buka file `database_config.php`.
   - Sesuaikan konfigurasi database (host, username, password, nama database) sesuai dengan pengaturan lokal Anda.

   ```php
   // Contoh konfigurasi
   $host = 'localhost';
   $username = 'root';
   $password = '';
   $database = 'sistem_penilaian';
   ```

4. **Jalankan Aplikasi**
   - Buka browser dan akses `http://localhost/nama_folder_project/index.php`
   - Anda bisa mulai menggunakan aplikasi untuk mengelola data karyawan dan melakukan penilaian.

## Struktur File Utama
- `index.php` : Halaman utama aplikasi
- `ahp_kriteria.php` : Pengelolaan kriteria AHP
- `ahp_alternatif.php` : Pengelolaan alternatif/karyawan
- `ranking_final_new.php` : Hasil ranking akhir
- `ranking_hybrid.php` : Hasil ranking dengan metode hybrid
- `tambah_karyawan.php` : Form tambah karyawan
- `edit_karyawan.php` : Edit data karyawan
- `karyawan_model.php` : Model data karyawan
- `database_config.php` : Konfigurasi database
- `sistem_penilaian.sql` : File SQL untuk database

## Catatan
- Pastikan web server dan database sudah berjalan sebelum mengakses aplikasi.
- Jika ada error terkait koneksi database, cek kembali konfigurasi di `database_config.php`.

## Lisensi
Project ini dibuat untuk keperluan pembelajaran dan portfolio.

---

Jika ada pertanyaan atau kendala, silakan hubungi pembuat melalui halaman repository ini. 