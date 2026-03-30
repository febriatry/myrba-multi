<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('setting_web')) {
            return;
        }

        Schema::table('setting_web', function (Blueprint $table) {
            if (!Schema::hasColumn('setting_web', 'setor_enabled_from')) {
                $table->dateTime('setor_enabled_from')->nullable();
            }
        });

        $exists = DB::table('setting_web')->exists();
        if ($exists) {
            DB::table('setting_web')->whereNull('setor_enabled_from')->update([
                'setor_enabled_from' => '2026-04-01 00:00:00',
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('setting_web')) {
            return;
        }
        if (Schema::hasColumn('setting_web', 'setor_enabled_from')) {
            Schema::table('setting_web', function (Blueprint $table) {
                $table->dropColumn('setor_enabled_from');
            });
        }
    }
};

