<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('active_ppps')) {
            Schema::table('active_ppps', function (Blueprint $table) {
                if (!Schema::hasColumn('active_ppps', 'router_id')) {
                    $table->unsignedBigInteger('router_id')->nullable()->after('id')->index();
                }
            });
            Schema::table('active_ppps', function (Blueprint $table) {
                $table->unique(['router_id', 'name'], 'active_ppps_router_name_unique');
            });
        }

        if (Schema::hasTable('secret_ppps')) {
            Schema::table('secret_ppps', function (Blueprint $table) {
                if (!Schema::hasColumn('secret_ppps', 'router_id')) {
                    $table->unsignedBigInteger('router_id')->nullable()->after('id')->index();
                }
            });
            Schema::table('secret_ppps', function (Blueprint $table) {
                $table->unique(['router_id', 'username'], 'secret_ppps_router_username_unique');
            });
        }

        if (Schema::hasTable('profile_pppoes')) {
            Schema::table('profile_pppoes', function (Blueprint $table) {
                if (!Schema::hasColumn('profile_pppoes', 'router_id')) {
                    $table->unsignedBigInteger('router_id')->nullable()->after('id')->index();
                }
            });
            Schema::table('profile_pppoes', function (Blueprint $table) {
                if (Schema::hasColumn('profile_pppoes', 'name')) {
                    $table->dropUnique('profile_pppoes_name_unique');
                }
            });
            Schema::table('profile_pppoes', function (Blueprint $table) {
                $table->unique(['router_id', 'name'], 'profile_pppoes_router_name_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('active_ppps')) {
            Schema::table('active_ppps', function (Blueprint $table) {
                $table->dropUnique('active_ppps_router_name_unique');
                if (Schema::hasColumn('active_ppps', 'router_id')) {
                    $table->dropColumn('router_id');
                }
            });
        }

        if (Schema::hasTable('secret_ppps')) {
            Schema::table('secret_ppps', function (Blueprint $table) {
                $table->dropUnique('secret_ppps_router_username_unique');
                if (Schema::hasColumn('secret_ppps', 'router_id')) {
                    $table->dropColumn('router_id');
                }
            });
        }

        if (Schema::hasTable('profile_pppoes')) {
            Schema::table('profile_pppoes', function (Blueprint $table) {
                $table->dropUnique('profile_pppoes_router_name_unique');
                $table->unique('name', 'profile_pppoes_name_unique');
                if (Schema::hasColumn('profile_pppoes', 'router_id')) {
                    $table->dropColumn('router_id');
                }
            });
        }
    }
};

