<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingMikrotikSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('settingmikrotiks')->insert([
            'identitas_router' => 'CCR1016-12G',
            'host' => '103.188.173.33',
            'port' => 1122,
            'username' => 'bot',
            'password' => 'bot14045',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
