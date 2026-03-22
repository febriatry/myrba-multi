<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SettingWebSeeder extends Seeder
{
    public function run()
    {
        DB::table('setting_web')->insert([
            'nama_perusahaan'       => 'PT. Tecanusa',
            'telepon_perusahaan'    => '083874731480',
            'email'                 => 'info@contoh.com',
            'no_wa'                 => '083874731480',
            'alamat'                => 'Jl. Contoh No. 123, Jakarta',
            'deskripsi_perusahaan' => 'Perusahaan yang bergerak di bidang teknologi dan informasi.',
            'logo'                  => 'logo.png',
            'url_tripay'            => 'https://tripay.co.id/api-sandbox/',
            'api_key_tripay'        => 'DEV-wdWWQLZNPbOLCrgCJYSOwdOxmPEbqMid6EaCcEfI',
            'kode_merchant'         => 'T25329',
            'private_key'           => 'AjWFE-HVt43-rEyO2-SOTw5-07X6d',
            'created_at'            => Carbon::now(),
            'updated_at'            => Carbon::now(),
        ]);
    }
}
