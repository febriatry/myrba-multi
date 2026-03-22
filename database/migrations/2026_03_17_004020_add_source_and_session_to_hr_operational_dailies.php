<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_operational_dailies')) {
            return;
        }

        Schema::table('hr_operational_dailies', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_operational_dailies', 'source')) {
                $table->string('source', 20)->default('manual')->after('amount')->index();
            }
            if (!Schema::hasColumn('hr_operational_dailies', 'session_id')) {
                $table->unsignedBigInteger('session_id')->nullable()->after('source')->unique();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hr_operational_dailies')) {
            return;
        }

        Schema::table('hr_operational_dailies', function (Blueprint $table) {
            if (Schema::hasColumn('hr_operational_dailies', 'session_id')) {
                $table->dropUnique(['session_id']);
                $table->dropColumn('session_id');
            }
            if (Schema::hasColumn('hr_operational_dailies', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};

