# Blueprint Aplikasi Android myrba-admin

## Tujuan
- Menyediakan aplikasi Android admin yang fokus pada operasional inti.
- Menampilkan menu dinamis berdasarkan role/permission user.
- Menjaga semua otorisasi tetap tervalidasi di backend.

## Ruang Lingkup Fitur
- Data Pelanggan
  - Request Data Pelanggan
  - Daftar Pelanggan
- Tiket Aduan
  - Daftar Tiket
- Informasi Management
  - Daftar Informasi
- Keuangan
  - Daftar Tagihan
- Kelola Layanan
  - ODC
  - ODP
- PPOE
  - PPP Aktif
  - PPP Non Aktif

## Prinsip Arsitektur
- Android hanya bertindak sebagai client.
- Permission enforcement wajib di backend, bukan hanya di UI.
- UI membaca permission untuk menentukan menu dan aksi yang tampil.
- Semua list endpoint memakai pagination, filter, sort.

## Arsitektur Teknis
- Client: Kotlin + Jetpack Compose atau XML + MVVM.
- Networking: Retrofit + OkHttp interceptor token.
- State: ViewModel + Flow/LiveData.
- Storage: DataStore untuk token/sesi.
- Backend: API Laravel yang sudah ada + endpoint tambahan untuk menu/permission.

## Kontrak Auth dan Permission
- Login
  - Input: username/email + password
  - Output: access token + profil user + role + permission list
- Me
  - Output: profil user + role + permission list terbaru
- Logout
  - Invalidasi token

## Struktur Menu Berdasarkan Permission
- Menu ditampilkan jika permission terpenuhi.
- Aksi detail (view/create/edit/delete) juga diverifikasi per permission.

## Matrix Menu ke Permission
| Menu Android | Endpoint Utama | Permission Minimal |
|---|---|---|
| Request Data Pelanggan | `GET /api/admin/request-pelanggan` | `pelanggan view` |
| Daftar Pelanggan | `GET /api/admin/pelanggans` | `pelanggan view` |
| Daftar Tiket Aduan | `GET /api/admin/tiket-aduans` | `tiket aduan view` |
| Daftar Informasi | `GET /api/admin/informasi-management` | `informasi management view` |
| Daftar Tagihan | `GET /api/admin/tagihans` | `tagihan view` |
| ODC | `GET /api/admin/odcs` | `odc view` |
| ODP | `GET /api/admin/odps` | `odp view` |
| PPP Aktif | `GET /api/admin/ppp/active` | `ppp active view` |
| PPP Non Aktif | `GET /api/admin/ppp/non-active` | `ppp non active view` |

## Kontrak Respons API Standar
- Sukses:
  - `success: true`
  - `message: string`
  - `data: array|object`
  - `meta: { page, limit, total }` untuk list
- Gagal:
  - `success: false`
  - `message: string`
  - `errors` opsional

## Spesifikasi Tiap Modul
- Request Data Pelanggan
  - Filter: status, tanggal, area
  - Aksi opsional: approve/reject jika permission ada
- Daftar Pelanggan
  - Filter: no layanan, nama, area, status
- Tiket Aduan
  - Filter: status, prioritas, tanggal
- Informasi Management
  - List publikasi internal
- Daftar Tagihan
  - Filter: status bayar, periode, pelanggan
- ODC/ODP
  - Filter: area, nama, code
- PPP Aktif/Non Aktif
  - Filter: username, layanan, area

## Tahapan Implementasi
1. Fondasi auth, token, interceptor, error handler.
2. Endpoint `me` + permission payload untuk dynamic menu.
3. Implementasi layar list prioritas:
   - Daftar Tagihan
   - Daftar Pelanggan
   - Tiket Aduan
4. Implementasi ODC/ODP + PPP aktif/non aktif.
5. Optimasi UX:
   - caching ringan
   - pull to refresh
   - offline fallback sederhana
6. UAT per role dan hardening security.

## Strategi Role Testing
- Role Super Admin
  - Semua menu dan aksi muncul.
- Role Keuangan
  - Fokus Daftar Tagihan, read-only pelanggan.
- Role NOC/Teknisi
  - Fokus ODC/ODP, PPP aktif/non aktif.
- Role CS
  - Fokus request pelanggan dan tiket aduan.

## Keamanan
- Semua endpoint admin wajib auth token.
- Semua endpoint admin wajib cek permission.
- Token refresh dan logout wajib dihandle.
- Data sensitif tidak disimpan plain text di device.

## Risiko dan Mitigasi
- Perbedaan naming permission web vs mobile
  - Mitigasi: satu sumber mapping permission di backend.
- Endpoint tidak konsisten
  - Mitigasi: standardisasi response contract.
- Data list berat
  - Mitigasi: pagination ketat + server-side filter.

## Deliverable Fase Analisa
- Dokumen blueprint ini.
- Daftar endpoint final per modul.
- Daftar permission final per menu/aksi.
- Rencana sprint implementasi Android + backend API gap.
