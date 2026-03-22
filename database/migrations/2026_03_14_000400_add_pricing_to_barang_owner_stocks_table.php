<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('barang_owner_stocks')) {
            Schema::table('barang_owner_stocks', function (Blueprint $table) {
                if (!Schema::hasColumn('barang_owner_stocks', 'hpp_unit')) {
                    $table->unsignedBigInteger('hpp_unit')->default(0)->after('qty');
                }
                if (!Schema::hasColumn('barang_owner_stocks', 'harga_jual_unit')) {
                    $table->unsignedBigInteger('harga_jual_unit')->default(0)->after('hpp_unit');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('barang_owner_stocks')) {
            Schema::table('barang_owner_stocks', function (Blueprint $table) {
                foreach (['harga_jual_unit', 'hpp_unit'] as $col) {
                    if (Schema::hasColumn('barang_owner_stocks', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

