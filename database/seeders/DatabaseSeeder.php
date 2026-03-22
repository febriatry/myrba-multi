<?php

namespace Database\Seeders;

use Database\Seeders\UnitSatuanSeeder as SeedersUnitSatuanSeeder;
use Database\Seeders\KategoriBarangSeeder as SeedersKategoriBarangSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\{
    SettingWebSeeder,
    SettingMikrotikSeeder,
    AreaSeeder,
    PacageKategoriSeeder,
    PacageSeeder,
    OdcSeeder,
    OdpSeeder,
    BankSeeder,
    OltSeeder,
    CategoryPemasukanSeeder,
    RoleAndPermissionSeeder,
    UnitSatuanSeeder,
    KategoriBarangSeeder
};

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            SettingWebSeeder::class,
            UserSeeder::class,
            RoleAndPermissionSeeder::class,
            DefaultPlanEntitlementsSeeder::class,
            PlatformOwnerUserSeeder::class,
            SettingMikrotikSeeder::class,
            AreaSeeder::class,
            PacageKategoriSeeder::class,
            PacageSeeder::class,
            OdcSeeder::class,
            OdpSeeder::class,
            BankSeeder::class,
            OltSeeder::class,
            CategoryPemasukanSeeder::class,
            UnitSatuanSeeder::class,
            KategoriBarangSeeder::class,
            ConfigPesanNotifSeeder::class,
            WaTemplateMappingSeeder::class,
        ]);
    }
}
