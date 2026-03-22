<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('investor_wallet_histories')) {
            Schema::table('investor_wallet_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('investor_wallet_histories', 'rule_id')) {
                    $table->unsignedBigInteger('rule_id')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('investor_wallet_histories', 'tagihan_id')) {
                    $table->unsignedBigInteger('tagihan_id')->nullable()->after('rule_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('investor_wallet_histories')) {
            Schema::table('investor_wallet_histories', function (Blueprint $table) {
                if (Schema::hasColumn('investor_wallet_histories', 'tagihan_id')) {
                    $table->dropColumn('tagihan_id');
                }
                if (Schema::hasColumn('investor_wallet_histories', 'rule_id')) {
                    $table->dropColumn('rule_id');
                }
            });
        }
    }
};

