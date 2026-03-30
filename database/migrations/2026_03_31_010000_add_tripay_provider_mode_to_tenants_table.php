<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'tripay_provider_mode')) {
                $table->string('tripay_provider_mode', 20)->nullable()->after('tripay_private_key');
                $table->index('tripay_provider_mode', 'tenants_tripay_provider_mode_index');
            }
        });

        DB::table('tenants')
            ->whereNull('tripay_provider_mode')
            ->update(['tripay_provider_mode' => 'owner']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'tripay_provider_mode')) {
                $table->dropIndex('tenants_tripay_provider_mode_index');
                $table->dropColumn('tripay_provider_mode');
            }
        });
    }
};
