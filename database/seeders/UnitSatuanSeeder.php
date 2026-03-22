<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UnitSatuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('unit_satuan')->insert([
            ['nama_unit_satuan' => 'Pcs', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama_unit_satuan' => 'Box', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama_unit_satuan' => 'Kg', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['nama_unit_satuan' => 'Ltr', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
