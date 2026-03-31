<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('setting_web')) {
            return;
        }
        Schema::table('setting_web', function (Blueprint $table) {
            if (! Schema::hasColumn('setting_web', 'wa_template_defaults_json')) {
                $table->json('wa_template_defaults_json')->nullable()->after('is_wa_welcome_active');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_web')) {
            return;
        }
        Schema::table('setting_web', function (Blueprint $table) {
            if (Schema::hasColumn('setting_web', 'wa_template_defaults_json')) {
                $table->dropColumn('wa_template_defaults_json');
            }
        });
    }
};
