# Rekomendasi Final Cron aaPanel

## Tujuan
- Menjadikan cron terpusat lewat Laravel Scheduler.
- Menghindari duplikasi job dari cron terpisah.

## Job aaPanel yang dipertahankan
- Tambahkan satu job saja:

```bash
* * * * * cd /www/wwwroot/myrba && /usr/bin/php artisan schedule:run >> /www/wwwlogs/myrba-scheduler.log 2>&1
```

## Jadwal yang sudah diatur di aplikasi
- `wa:sync-templates` setiap jam.
- `pelanggan:auto-isolate` jam `00:30` setiap hari.
- `tagihan:create` jam `07:00` setiap hari.
- `tagihan:send-wa` setiap 10 menit.

## Job aaPanel lama yang harus dinonaktifkan
- Job terpisah yang menembak:
  - Semua job lama yang menembak file `public/cron/*.php`
- Job berbasis URL/curl ke endpoint cron.

## Kenapa harus dinonaktifkan
- Menghindari eksekusi ganda.
- Menghindari error HTTP/2 dari curl URL.
- Monitoring jadi cukup dari satu log scheduler.

## Validasi setelah migrasi
- Jalankan:

```bash
cd /www/wwwroot/myrba
php artisan schedule:list
php artisan schedule:run
```

- Cek log:
  - `/www/wwwlogs/myrba-scheduler.log`
  - `storage/logs/laravel.log`
