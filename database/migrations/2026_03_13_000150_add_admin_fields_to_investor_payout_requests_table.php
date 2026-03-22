<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('investor_payout_requests')) {
            Schema::table('investor_payout_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('investor_payout_requests', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
                }
                if (!Schema::hasColumn('investor_payout_requests', 'pengeluaran_id')) {
                    $table->unsignedBigInteger('pengeluaran_id')->nullable()->after('approved_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('investor_payout_requests')) {
            Schema::table('investor_payout_requests', function (Blueprint $table) {
                if (Schema::hasColumn('investor_payout_requests', 'pengeluaran_id')) {
                    $table->dropColumn('pengeluaran_id');
                }
                if (Schema::hasColumn('investor_payout_requests', 'approved_by')) {
                    $table->dropColumn('approved_by');
                }
            });
        }
    }
};

