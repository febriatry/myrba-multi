<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wa_message_status_logs')) {
            return;
        }

        Schema::table('wa_message_status_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('wa_message_status_logs', 'billing_mode')) {
                $table->string('billing_mode', 20)->nullable()->after('provider');
                $table->index('billing_mode', 'wa_msg_logs_billing_mode_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wa_message_status_logs')) {
            return;
        }

        Schema::table('wa_message_status_logs', function (Blueprint $table) {
            if (Schema::hasColumn('wa_message_status_logs', 'billing_mode')) {
                $table->dropIndex('wa_msg_logs_billing_mode_index');
                $table->dropColumn('billing_mode');
            }
        });
    }
};
