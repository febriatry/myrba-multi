<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_pengeluarans')) {
            return;
        }

        Schema::table('category_pengeluarans', function (Blueprint $table) {
            if (! Schema::hasColumn('category_pengeluarans', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->index('tenant_id', 'category_pengeluarans_tenant_id_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('category_pengeluarans')) {
            return;
        }
        Schema::table('category_pengeluarans', function (Blueprint $table) {
            if (Schema::hasColumn('category_pengeluarans', 'tenant_id')) {
                $table->dropIndex('category_pengeluarans_tenant_id_index');
                $table->dropColumn('tenant_id');
            }
        });
    }
};
