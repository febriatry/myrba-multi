<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'wa_provider_mode')) {
                $table->string('wa_provider_mode', 20)->default('developer')->after('quota_json');
            }
            if (!Schema::hasColumn('tenants', 'wa_ivosight_base_url')) {
                $table->string('wa_ivosight_base_url', 255)->nullable()->after('wa_provider_mode');
            }
            if (!Schema::hasColumn('tenants', 'wa_ivosight_api_key')) {
                $table->string('wa_ivosight_api_key', 255)->nullable()->after('wa_ivosight_base_url');
            }
            if (!Schema::hasColumn('tenants', 'wa_ivosight_sender_id')) {
                $table->string('wa_ivosight_sender_id', 100)->nullable()->after('wa_ivosight_api_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            foreach (['wa_ivosight_sender_id', 'wa_ivosight_api_key', 'wa_ivosight_base_url', 'wa_provider_mode'] as $col) {
                if (Schema::hasColumn('tenants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

