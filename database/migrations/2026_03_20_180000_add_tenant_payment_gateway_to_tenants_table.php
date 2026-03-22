<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'tripay_base_url')) {
                $table->string('tripay_base_url', 255)->nullable()->after('wa_ivosight_sender_id');
            }
            if (!Schema::hasColumn('tenants', 'tripay_api_key')) {
                $table->string('tripay_api_key', 255)->nullable()->after('tripay_base_url');
            }
            if (!Schema::hasColumn('tenants', 'tripay_merchant_code')) {
                $table->string('tripay_merchant_code', 100)->nullable()->after('tripay_api_key');
            }
            if (!Schema::hasColumn('tenants', 'tripay_private_key')) {
                $table->string('tripay_private_key', 255)->nullable()->after('tripay_merchant_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            foreach (['tripay_private_key', 'tripay_merchant_code', 'tripay_api_key', 'tripay_base_url'] as $col) {
                if (Schema::hasColumn('tenants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

