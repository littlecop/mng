# MNG Finance Admin Panel

Panel dashboard administrasi keuangan berbasis PHP & MySQL dengan desain modern yang responsif.

## Fitur

- Autentikasi admin, ringkasan saldo, pemasukan/pengeluaran bulan berjalan, dan grafik tren (`dashboard.php`).
- Manajemen akun, kategori, transaksi, anggaran, serta laporan periode kustom (`accounts.php`, `categories.php`, `transactions.php`, `budgets.php`, `reports.php`).
- Desain light & compact memakai Bootstrap 5 + Chart.js (lihat `assets/css/styles.css` dan `assets/js/dashboard.js`).

## Instalasi Cepat

1. Import `database.sql` ke MySQL untuk membuat struktur dan akun awal.
2. Atur kredensial database di `config.php` bila diperlukan.
3. Jalankan situs pada server PHP (mis. XAMPP) dan akses `index.php`.
4. Masuk dengan akun default `admin@example.com` / `admin123` dan segera ganti sandi.

## Dependensi

- PHP 8+ dengan ekstensi `pdo_mysql`.
- MySQL/MariaDB.
- Bootstrap & Chart.js via CDN (butuh akses internet saat rendering halaman).
