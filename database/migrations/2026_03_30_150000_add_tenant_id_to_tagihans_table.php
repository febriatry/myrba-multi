<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tagihans')) {
            return;
        }

        Schema::table('tagihans', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihans', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->index('tenant_id', 'tagihans_tenant_id_index');
            }
        });

        // Unique per-tenant untuk nomor tagihan
        Schema::table('tagihans', function (Blueprint $table) {
            try {
                $table->unique(['tenant_id', 'no_tagihan'], 'tagihans_tenant_no_unique');
            } catch (\Throwable $e) {
                // abaikan jika sudah ada
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tagihans')) {
            return;
        }
        Schema::table('tagihans', function (Blueprint $table) {
            if (Schema::hasColumn('tagihans', 'tenant_id')) {
                $table->dropIndex('tagihans_tenant_id_index');
                $table->dropUnique('tagihans_tenant_no_unique');
                $table->dropColumn('tenant_id');
            }
        });
    }
};
