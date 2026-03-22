<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigPesanNotifSeeder extends Seeder
{
    public function run()
    {
        DB::table('config_pesan_notif')->insert([
            'pesan_notif_pendaftaran' => <<<EOT
Selamat datang di {nama_perusahaan}

Kami senang Anda telah bergabung dengan layanan WiFi kami.
Penting yang perlu Anda ketahui:

*Nama :* {nama_pelanggan}
*Alamat :* {alamat}
*Paket Layanan :* {paket_layanan}
*No Layanan :* {no_layanan}

Jika Anda memiliki pertanyaan atau membutuhkan bantuan tambahan, jangan ragu untuk menghubungi kami di {no_wa} atau melalui email ke {email}.

Terima kasih atas kepercayaan Anda kepada kami. Selamat menikmati koneksi internet yang stabil dan cepat!

Salam hangat,
{nama_admin}-{nama_perusahaan}
EOT,
            'pesan_notif_tagihan' => <<<EOT
Pelanggan {nama_perusahaan}

Yth. *{nama_pelanggan}*

Kami sampaikan tagihan layanan internet bulan *{periode}*
Dengan ID Pelanggan *{no_layanan}*

Sebesar *{total_bayar}*

Pembayaran paling lambat di tanggal *{tanggal_jatuh_tempo}*
Untuk Menghindari Isolir *(kecepatan menurun otomatis)* di jaringan anda.
EOT,
            'pesan_notif_pembayaran' => <<<EOT
Yth. {nama_pelanggan}

Berikut ini adalah data pembayaran yang telah kami terima:

*ID Pelanggan :* {no_layanan}
*No Tagihan :* {no_tagihan}
*Nama Pelanggan :* {nama_pelanggan}
*Nominal :* {nominal}
*Metode Pembayaran :* {metode_bayar}
*Tanggal :* {tanggal_bayar}

*Link invoice :* {link_invoice}
EOT,
            'pesan_notif_kirim_invoice' => <<<EOT
Yth. {nama_pelanggan}

Berikut ini adalah data pembayaran yang telah kami terima:

*ID Pelanggan :* {no_layanan}
*No Tagihan :* {no_tagihan}
*Nama Pelanggan :* {nama_pelanggan}
*Nominal :* {nominal}
*Metode Pembayaran :* {metode_bayar}
*Tanggal :* {tanggal_bayar}

*Link invoice :* {link_invoice}
EOT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
