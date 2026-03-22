# Integrasi Template Ivosight

Dokumen ini menyimpan acuan integrasi template WhatsApp agar pengiriman sesuai kebijakan approval template provider.

## Status Saat Ini

- Gateway sudah mendukung endpoint template:
  - `POST /api/v1/messages/send-template-message`
- Flow notifikasi aplikasi masih dominan kirim text (`sendText`) untuk:
  - pendaftaran
  - tagihan
  - pembayaran
  - invoice
- Flag `IVOSIGHT_USE_TEMPLATE` tersedia di konfigurasi, namun belum digunakan sebagai switch penuh di seluruh flow.

## Strategi Integrasi

1. Daftarkan template di dashboard Ivosight per use case:
   - `daftar`
   - `tagihan`
   - `bayar`
   - `invoice`
2. Simpan mapping `type_pesan -> template_id` di aplikasi.
3. Simpan mapping parameter komponen template ke data internal aplikasi.
4. Aktifkan switch:
   - jika `IVOSIGHT_USE_TEMPLATE=true`, kirim via `sendTemplate`
   - jika `IVOSIGHT_USE_TEMPLATE=false`, kirim via `sendText`
5. Siapkan fallback:
   - jika kirim template gagal karena invalid mapping, fallback ke text dan log kegagalan.
6. Pastikan webhook status tetap dicatat untuk message lifecycle:
   - `sent`
   - `delivered`
   - `read`
   - `failed`

## Checklist UAT

- Semua template sudah status approved di Ivosight.
- Semua placeholder internal berhasil dipetakan ke komponen template.
- Pengiriman per jenis notifikasi berhasil pada nomor uji.
- Status webhook masuk ke monitor status WA.
- Export CSV monitor status menampilkan event sesuai periode uji.
