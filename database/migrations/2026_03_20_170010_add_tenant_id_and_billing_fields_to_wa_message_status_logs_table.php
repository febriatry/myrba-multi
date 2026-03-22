<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_message_status_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('wa_message_status_logs', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->index('tenant_id', 'wa_msg_logs_tenant_id_index');
            }
            if (!Schema::hasColumn('wa_message_status_logs', 'provider')) {
                $table->string('provider', 30)->nullable()->after('type');
                $table->index('provider', 'wa_msg_logs_provider_index');
            }
            if (!Schema::hasColumn('wa_message_status_logs', 'cost_units')) {
                $table->unsignedInteger('cost_units')->default(1)->after('provider');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wa_message_status_logs', function (Blueprint $table) {
            if (Schema::hasColumn('wa_message_status_logs', 'cost_units')) {
                $table->dropColumn('cost_units');
            }
            if (Schema::hasColumn('wa_message_status_logs', 'provider')) {
                $table->dropIndex('wa_msg_logs_provider_index');
                $table->dropColumn('provider');
            }
            if (Schema::hasColumn('wa_message_status_logs', 'tenant_id')) {
                $table->dropIndex('wa_msg_logs_tenant_id_index');
                $table->dropColumn('tenant_id');
            }
        });
    }
};

