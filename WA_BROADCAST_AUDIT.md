# Audit WA Broadcast

## Komponen Aktif

- Gateway WA Ivosight:
  - `app/Services/WhatsApp/IvosightGateway.php`
- Broadcast manual:
  - menu `sendnotifs`
  - endpoint `POST /kirim_pesan`
- Notifikasi otomatis:
  - helper `sendNotifWa(...)`
- Webhook status:
  - `GET/POST /webhooks/ivosight`
- Monitor status:
  - `GET /wa-status-logs`
  - `GET /wa-status-logs/export-csv`

## Penyesuaian yang Sudah Ada

- Endpoint kirim text menggunakan path v1.
- Header auth sudah `X-API-KEY`.
- Status webhook sudah disimpan ke `wa_message_status_logs`.
- Monitor status sudah mendukung:
  - filter status
  - filter nomor
  - filter message id
  - filter rentang tanggal
  - export CSV

## Temuan Legacy

- `WaBlastController` masih ada di codebase, namun tidak lagi dipakai route aktif.
- View `wa-blast/*` masih menjadi artefak lama.
- Route alias `/wa-blast` tetap dipertahankan untuk kompatibilitas.

## Rekomendasi

- Pertahankan alias `/wa-blast` untuk backward compatibility.
- Jika tidak dibutuhkan lagi, arsipkan controller/view legacy WA Blast.
- Lanjutkan integrasi template approved Ivosight agar flow notifikasi sesuai compliance.
