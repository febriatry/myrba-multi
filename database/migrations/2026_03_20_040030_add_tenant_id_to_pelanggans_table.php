<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggans', function (Blueprint $table) {
            if (!Schema::hasColumn('pelanggans', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->index('tenant_id', 'pelanggans_tenant_id_index');
            }
        });

        DB::table('pelanggans')->whereNull('tenant_id')->update(['tenant_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('pelanggans', function (Blueprint $table) {
            if (Schema::hasColumn('pelanggans', 'tenant_id')) {
                $table->dropIndex('pelanggans_tenant_id_index');
                $table->dropColumn('tenant_id');
            }
        });
    }
};

