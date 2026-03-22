# v1.2 Release Notes

## Ringkasan
- Migrasi WA Blast ke WhatsApp Broadcast (Ivosight); hapus sesi QR.
- Halaman pengaturan WA Broadcast (placeholder .env).
- Tabel Pemasukan, Pengeluaran, Tagihan lebih ramah mobile (DataTables Responsive).
- Perbaikan urutan kolom Tagihan (ID, Nama, No Tagihan) dan kompatibilitas responsif.
- Format invoice kompatibel printer POS (80mm, monospace).
- Export Buku Kas kini preview (stream) dan gaya “ledger” (monospace + dashed lines).

## Detail Perubahan

### WhatsApp Broadcast (Ivosight)
- Konfigurasi default provider ivosight: [config/whatsapp.php]
- Variabel .env contoh: IVOSIGHT_BASE_URL, IVOSIGHT_API_KEY, IVOSIGHT_SENDER_ID, IVOSIGHT_USE_TEMPLATE
- Service: IvosightGateway (text & template)
- Webhook endpoint: GET/POST /webhooks/ivosight
- Halaman pengaturan: /wa-config (placeholder tampilan konfigurasi)
- Alihkan rute lama wa-blast ke wa-config sebagai alias

### UI Mobile
- Pemasukan/Pengeluaran:
  - Filter menjadi kolom penuh di mobile; tabel menggunakan DataTables Responsive.
  - Scroll horizontal aktif; prioritas kolom disesuaikan.
- Tagihan:
  - Filter kolom penuh di mobile; tabel responsive + scrollX.
  - Urutan kolom: ID Pelanggan, Nama Pelanggan, lalu No Tagihan.
  - Perbaikan error responsif (kolom header/data sinkron).

### Invoice POS
- Template invoice diubah untuk printer thermal:
  - Lebar konten 80mm, font monospace, layout ringkas.

### Laporan Buku Kas
- Export menjadi preview (PDF stream).
- Tampilan gaya ledger: font monospace, garis putus-putus, tabel rapat.

## Catatan
- Tidak ada perubahan metode pembuatan dokumen PDF, hanya format output di template.
