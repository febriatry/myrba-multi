<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transaksi_details')) {
            Schema::table('transaksi_details', function (Blueprint $table) {
                if (!Schema::hasColumn('transaksi_details', 'hpp_unit')) {
                    $table->unsignedBigInteger('hpp_unit')->default(0)->after('jumlah');
                }
                if (!Schema::hasColumn('transaksi_details', 'harga_jual_unit')) {
                    $table->unsignedBigInteger('harga_jual_unit')->default(0)->after('hpp_unit');
                }
                if (!Schema::hasColumn('transaksi_details', 'purpose')) {
                    $table->string('purpose', 20)->nullable()->after('harga_jual_unit');
                }
                if (!Schema::hasColumn('transaksi_details', 'purpose_scope')) {
                    $table->string('purpose_scope', 20)->nullable()->after('purpose');
                }
                if (!Schema::hasColumn('transaksi_details', 'target_pelanggan_id')) {
                    $table->unsignedBigInteger('target_pelanggan_id')->nullable()->after('purpose_scope');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaksi_details')) {
            Schema::table('transaksi_details', function (Blueprint $table) {
                foreach (['target_pelanggan_id', 'purpose_scope', 'purpose', 'harga_jual_unit', 'hpp_unit'] as $col) {
                    if (Schema::hasColumn('transaksi_details', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

