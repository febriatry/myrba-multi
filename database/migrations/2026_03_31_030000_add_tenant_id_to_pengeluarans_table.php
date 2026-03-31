<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pengeluarans')) {
            return;
        }

        Schema::table('pengeluarans', function (Blueprint $table) {
            if (! Schema::hasColumn('pengeluarans', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->index('tenant_id', 'pengeluarans_tenant_id_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pengeluarans')) {
            return;
        }
        Schema::table('pengeluarans', function (Blueprint $table) {
            if (Schema::hasColumn('pengeluarans', 'tenant_id')) {
                $table->dropIndex('pengeluarans_tenant_id_index');
                $table->dropColumn('tenant_id');
            }
        });
    }
};
