<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggans', function (Blueprint $table) {
            if (!Schema::hasColumn('pelanggans', 'referral_bonus_paid_at')) {
                $table->timestamp('referral_bonus_paid_at')->nullable()->after('kode_referal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pelanggans', function (Blueprint $table) {
            if (Schema::hasColumn('pelanggans', 'referral_bonus_paid_at')) {
                $table->dropColumn('referral_bonus_paid_at');
            }
        });
    }
};

