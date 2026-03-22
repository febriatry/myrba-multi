<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pelanggans')) {
            Schema::table('pelanggans', function (Blueprint $table) {
                if (!Schema::hasColumn('pelanggans', 'pending_paket_layanan')) {
                    $table->unsignedBigInteger('pending_paket_layanan')->nullable()->after('paket_layanan');
                }
                if (!Schema::hasColumn('pelanggans', 'pending_paket_effective_periode')) {
                    $table->string('pending_paket_effective_periode', 7)->nullable()->after('pending_paket_layanan');
                }
                if (!Schema::hasColumn('pelanggans', 'pending_paket_requested_by')) {
                    $table->unsignedBigInteger('pending_paket_requested_by')->nullable()->after('pending_paket_effective_periode');
                }
                if (!Schema::hasColumn('pelanggans', 'pending_paket_requested_at')) {
                    $table->timestamp('pending_paket_requested_at')->nullable()->after('pending_paket_requested_by');
                }
                if (!Schema::hasColumn('pelanggans', 'pending_paket_note')) {
                    $table->text('pending_paket_note')->nullable()->after('pending_paket_requested_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pelanggans')) {
            Schema::table('pelanggans', function (Blueprint $table) {
                foreach (['pending_paket_note', 'pending_paket_requested_at', 'pending_paket_requested_by', 'pending_paket_effective_periode', 'pending_paket_layanan'] as $col) {
                    if (Schema::hasColumn('pelanggans', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

