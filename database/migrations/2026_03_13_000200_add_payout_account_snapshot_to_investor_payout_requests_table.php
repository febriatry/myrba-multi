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
                if (!Schema::hasColumn('investor_payout_requests', 'payout_account_id')) {
                    $table->unsignedBigInteger('payout_account_id')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('investor_payout_requests', 'payout_type')) {
                    $table->string('payout_type', 20)->nullable()->after('payout_account_id');
                }
                if (!Schema::hasColumn('investor_payout_requests', 'payout_provider')) {
                    $table->string('payout_provider', 50)->nullable()->after('payout_type');
                }
                if (!Schema::hasColumn('investor_payout_requests', 'payout_account_name')) {
                    $table->string('payout_account_name', 100)->nullable()->after('payout_provider');
                }
                if (!Schema::hasColumn('investor_payout_requests', 'payout_account_number')) {
                    $table->string('payout_account_number', 100)->nullable()->after('payout_account_name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('investor_payout_requests')) {
            Schema::table('investor_payout_requests', function (Blueprint $table) {
                foreach (['payout_account_number', 'payout_account_name', 'payout_provider', 'payout_type', 'payout_account_id'] as $col) {
                    if (Schema::hasColumn('investor_payout_requests', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

