# Panduan Pembuatan APK WebView (Kotlin) untuk Myrba Admin

## Prasyarat
- Domain produksi HTTPS aktif (contoh: https://myrba.net).
- Endpoint konfigurasi tersedia: https://myrba.net/app-config.
- Aplikasi web sudah responsif mobile dan memiliki halaman cetak invoice POS.

## Langkah Pembuatan Proyek Android (Android Studio)
1. Buat proyek baru
   - Pilih “Empty Activity”.
   - Nama aplikasi: Myrba Admin.

2. Permissions & Config
   - AndroidManifest.xml:
     - Tambah uses-permission INTERNET.
   - Network Security Config (opsional, jika perlu aturan khusus):
     - Pastikan hanya HTTPS domain produksi yang diizinkan.

3. WebViewActivity
   - Inisialisasi WebView dengan pengaturan:
     - settings.javaScriptEnabled = true
     - settings.domStorageEnabled = true
     - CookieManager.getInstance().setAcceptCookie(true)
     - CookieManager.getInstance().setAcceptThirdPartyCookies(webView, true)
   - Handle back navigation:
     - if (webView.canGoBack()) webView.goBack() else finish()

4. Bootstrap dengan /app-config
   - Saat start, lakukan GET ke https://myrba.net/app-config.
   - Ambil base_url, login_url, logo_url (untuk splash/icon opsional).
   - Load login_url di WebView.

5. Download & Cetak Invoice POS
   - Pasang DownloadListener/WebViewClient untuk tangani link PDF.
   - Saat terdeteksi content application/pdf:
     - Unduh file ke cache atau panggil Intent ACTION_VIEW dengan MIME application/pdf.
     - Jika perangkat tidak punya viewer, arahkan install viewer atau gunakan print framework Android.

6. Sesi Login
   - CookieManager digunakan untuk persist session dari Web (laravel session cookie).
   - Tidak perlu implementasi token khusus untuk WebView (cukup session cookie).

7. UI & Branding
   - Splash screen sederhana menampilkan logo perusahaan (logo_url dari app-config).
   - Ikon aplikasi: gunakan logo perusahaan yang sama atau aset internal.

8. Build & Rilis
   - Generate keystore untuk signing (Build → Generate Signed Bundle/APK).
   - Pilih:
     - APK (untuk sideload/internal distribusi), atau
     - AAB (untuk Google Play).
   - Set minSdk sesuai kebutuhan WebView (umumnya 21+).
   - Proguard/R8: default rules sudah cukup; pastikan WebView tidak ter-obfuscate berlebihan.

9. Fitur Opsional
   - Push Notif: integrasikan FCM untuk notifikasi Waiting Review/pembayaran sukses.
   - Deep Link: tautan membuka langsung halaman detail (misal /active-ppps atau /invoice/{id}).

## Referensi Endpoint Web
- /app-config: metadata aplikasi (base_url, login_url, logo_url, is_webview_app).
- /invoice/{id}: preview PDF invoice POS 57x40mm (siap untuk printer thermal).

## Catatan
- Semua fungsi aplikasi web tetap berjalan; WebView hanya membungkus UI yang sudah ada.
- Pastikan update web dilakukan via HTTPS agar WebView tidak memblokir konten campuran.
