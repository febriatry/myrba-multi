<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KategoriBarangSeeder extends Seeder
{
    public function run()
    {
        DB::table('kategori_barang')->insert([
            ['nama_kategori_barang' => 'Elektronik', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama_kategori_barang' => 'Alat Tulis', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama_kategori_barang' => 'Furniture', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama_kategori_barang' => 'Makanan', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
