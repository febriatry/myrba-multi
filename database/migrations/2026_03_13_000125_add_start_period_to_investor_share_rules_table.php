<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('investor_share_rules') && !Schema::hasColumn('investor_share_rules', 'start_period')) {
            Schema::table('investor_share_rules', function (Blueprint $table) {
                $table->string('start_period', 7)->nullable()->after('package_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('investor_share_rules') && Schema::hasColumn('investor_share_rules', 'start_period')) {
            Schema::table('investor_share_rules', function (Blueprint $table) {
                $table->dropColumn('start_period');
            });
        }
    }
};

