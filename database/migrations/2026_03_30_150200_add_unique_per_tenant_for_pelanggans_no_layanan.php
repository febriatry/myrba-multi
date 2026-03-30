<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pelanggans')) {
            return;
        }
        Schema::table('pelanggans', function (Blueprint $table) {
            try {
                $table->unique(['tenant_id', 'no_layanan'], 'pelanggans_tenant_layanan_unique');
            } catch (\Throwable $e) {
                // abaikan jika sudah ada
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pelanggans')) {
            return;
        }
        Schema::table('pelanggans', function (Blueprint $table) {
            $table->dropUnique('pelanggans_tenant_layanan_unique');
        });
    }
};
