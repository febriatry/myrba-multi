# v1.1 Release Notes

## Ringkasan
- Penambahan ringkasan Tagihan per filter (jumlah & total terbayar/tertunggak).
- Estimasi pendapatan Pelanggan mengikuti filter halaman.
- Kategori pemasukan Internet per area otomatis & perintah backfill.
- Pemasukan: filter metode bayar + ringkasan sesuai filter.
- Pengeluaran: ringkasan jumlah & total sesuai filter.
- Laporan: export Buku Kas (ledger) format tanggal, kategori, keterangan, pemasukan, pengeluaran, saldo.
- Dashboard: grafik balok bulanan untuk Pemasukan vs Pengeluaran dan status Tagihan.
- PPP: tampilkan Last Disconnect; downtime hanya untuk akun non-aktif.

## Detail Perubahan

### Tagihan
- Endpoint ringkasan: GET /tagihans/summary, menampilkan:
  - paid_count, paid_sum, unpaid_count, unpaid_sum
- Integrasi UI: kartu ringkasan di halaman Tagihan; update saat filter berubah.
- Perbaikan route order untuk mencegah konflik dengan resource show.

### Pelanggan
- Estimasi pendapatan mengikuti filter area, paket, router, mode user, tanggal daftar; hanya status “Aktif”.
- UI: nilai estimasi pendapatan ter-update otomatis saat filter berubah.

### Pemasukan per Area
- Helper: getInternetIncomeCategoryIdForPelanggan(pelanggan_id) membuat/menggunakan kategori “Pemasukan internet - {Nama Area}”.
- Integrasi di:
  - TagihanController::validasiTagihan
  - Api\TagihanController::payWithSaldo
  - TripayCallbackController::handle (menyertakan referense_id tagihan)
- Artisan: income:backfill-categories
  - Opsi --dry-run untuk melihat perubahan
  - Memetakan pemasukan lama ke kategori area berdasar referense_id → tagihan → pelanggan

### Pemasukan
- Filter metode bayar (Cash, Transfer Bank, Payment Tripay, Saldo).
- Endpoint ringkasan pemasukan sesuai filter: GET /pemasukans/summary.
- UI: kartu jumlah transaksi & total pemasukan.

### Pengeluaran
- Endpoint ringkasan jumlah & total pengeluaran: GET /pengeluarans/summary.
- UI: kartu jumlah & total pengeluaran.

### Laporan Buku Kas
- Endpoint: GET laporans/export-kas
- PDF berisi ledger dengan kolom:
  - Tanggal, Kategori, Keterangan, Pemasukan, Pengeluaran, Saldo
- Pemasukan diringkas per hari per area (kategori “Pemasukan internet - {Nama Area}”), keterangan “Pembayaran Tagihan (N transaksi)”.
- Pengeluaran dicetak rinci per transaksi.

### Dashboard
- Finance bulanan: bar chart 12 bulan terakhir (income vs expense).
- Status Tagihan bulanan: bar chart 12 bulan terakhir (Sudah Bayar, Waiting Review, Belum Bayar).

### PPP
- Active PPP:
  - Kolom Last Disconnect ditambahkan (sumber last-logged-out dari secret PPP).
  - Tetap menampilkan Uptime.
- Non Active PPP:
  - Kolom Last Disconnect dan Downtime ditambahkan.
  - Downtime dihitung dari last-logged-out hingga saat ini.
- Detail Active PPP:
  - Menampilkan Downtime hanya saat akun offline; jika online tampil “-”.

## Catatan
- Tidak ada migrasi baru.
- Pastikan menjalankan perintah backfill setelah update untuk konsistensi kategori pemasukan area:
  - php artisan income:backfill-categories --dry-run
  - php artisan income:backfill-categories
