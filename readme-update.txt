Tujuan
- Update aplikasi ke versi v1.1 di server Linux dengan aman

Prasyarat
- Linux dengan akses sudo
- PHP 8.x, Composer, web server (Nginx/Apache), MySQL/MariaDB
- Git terpasang dan akses ke repository GitHub
- .env sudah terkonfigurasi

Langkah Update
1) Backup
- Backup file penting: .env, storage, public/uploads
- Backup database: mysqldump -u user -p dbname > backup.sql

2) Pull Rilis v1.1
- cd /path/to/app
- git fetch --all
- git checkout v1.1
  atau: git pull origin main && git checkout tags/v1.1

3) Install Dependencies
- composer install --no-dev --optimize-autoloader
- php artisan config:clear && php artisan cache:clear && php artisan route:clear

4) Optimize
- php artisan config:cache
- php artisan route:cache
- php artisan view:cache
- php artisan optimize

5) Jalankan Backfill Kategori Pemasukan Area
- php artisan income:backfill-categories --dry-run
- php artisan income:backfill-categories

6) Restart Layanan
- sudo systemctl restart php-fpm
- sudo systemctl reload nginx
  (atau restart apache2 jika menggunakan Apache)

7) Verifikasi
- Buka halaman Laporan dan Dashboard
- Cek ringkasan Pemasukan/Pengeluaran dan grafik bulanan
- Buka Tagihan untuk melihat ringkasan sesuai filter
- Buka PPP (Active/Non-Active) untuk kolom Last Disconnect/Downtime

Catatan Tambahan
- Tidak ada migrasi baru di v1.1
- Pastikan env TRIPAY/sejenis tetap valid jika menggunakan payment gateway
