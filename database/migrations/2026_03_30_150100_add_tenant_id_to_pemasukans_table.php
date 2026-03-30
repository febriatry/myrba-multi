<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pemasukans')) {
            return;
        }
        Schema::table('pemasukans', function (Blueprint $table) {
            if (! Schema::hasColumn('pemasukans', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->index('tenant_id', 'pemasukans_tenant_id_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pemasukans')) {
            return;
        }
        Schema::table('pemasukans', function (Blueprint $table) {
            if (Schema::hasColumn('pemasukans', 'tenant_id')) {
                $table->dropIndex('pemasukans_tenant_id_index');
                $table->dropColumn('tenant_id');
            }
        });
    }
};
