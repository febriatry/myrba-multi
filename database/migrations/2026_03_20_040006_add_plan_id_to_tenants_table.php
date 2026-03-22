<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable()->after('status');
                $table->index('plan_id', 'tenants_plan_id_index');
            }
        });

        DB::table('tenants')->whereNull('plan_id')->update(['plan_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'plan_id')) {
                $table->dropIndex('tenants_plan_id_index');
                $table->dropColumn('plan_id');
            }
        });
    }
};

