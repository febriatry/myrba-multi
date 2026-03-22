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
                if (!Schema::hasColumn('transaksi_details', 'owner_type')) {
                    $table->string('owner_type', 20)->default('office')->after('barang_id');
                }
                if (!Schema::hasColumn('transaksi_details', 'owner_user_id')) {
                    $table->unsignedBigInteger('owner_user_id')->nullable()->after('owner_type');
                }
                if (!Schema::hasColumn('transaksi_details', 'source_type')) {
                    $table->string('source_type', 30)->nullable()->after('owner_user_id');
                }
                if (!Schema::hasColumn('transaksi_details', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaksi_details')) {
            Schema::table('transaksi_details', function (Blueprint $table) {
                foreach (['source_id', 'source_type', 'owner_user_id', 'owner_type'] as $col) {
                    if (Schema::hasColumn('transaksi_details', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

