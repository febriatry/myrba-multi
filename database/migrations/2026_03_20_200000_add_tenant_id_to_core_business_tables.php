<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addTenantId(string $table): void
    {
        if (!Schema::hasTable($table) || Schema::hasColumn($table, 'tenant_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) {
            $tableBlueprint->unsignedBigInteger('tenant_id')->default(1)->after('id');
            $tableBlueprint->index('tenant_id');
        });

        DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => 1]);
    }

    public function up(): void
    {
        foreach ([
            'area_coverages',
            'packages',
            'package_categories',
            'odcs',
            'odps',
            'settingmikrotiks',
            'tagihans',
            'pemasukans',
            'pengeluarans',
            'category_pemasukans',
            'category_pengeluarans',
            'banks',
            'bank_accounts',
            'topups',
            'withdraws',
            'balance_histories',
            'barangs',
            'kategori_barangs',
            'unit_satuans',
            'transaksis',
            'transaksi_details',
        ] as $table) {
            $this->addTenantId($table);
        }
    }

    public function down(): void
    {
        foreach ([
            'area_coverages',
            'packages',
            'package_categories',
            'odcs',
            'odps',
            'settingmikrotiks',
            'tagihans',
            'pemasukans',
            'pengeluarans',
            'category_pemasukans',
            'category_pengeluarans',
            'banks',
            'bank_accounts',
            'topups',
            'withdraws',
            'balance_histories',
            'barangs',
            'kategori_barangs',
            'unit_satuans',
            'transaksis',
            'transaksi_details',
        ] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $tableBlueprint) {
                $tableBlueprint->dropIndex(['tenant_id']);
                $tableBlueprint->dropColumn('tenant_id');
            });
        }
    }
};
