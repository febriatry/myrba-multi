<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hr_employee_profiles')) {
            Schema::table('hr_employee_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('hr_employee_profiles', 'weekly_off_days')) {
                    $table->json('weekly_off_days')->nullable()->after('mandatory_deduction_value');
                }
            });
        }

        if (Schema::hasTable('hr_work_scheme_rules')) {
            Schema::table('hr_work_scheme_rules', function (Blueprint $table) {
                if (!Schema::hasColumn('hr_work_scheme_rules', 'overtime_start_time')) {
                    $table->time('overtime_start_time')->nullable()->after('end_time');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hr_work_scheme_rules')) {
            Schema::table('hr_work_scheme_rules', function (Blueprint $table) {
                if (Schema::hasColumn('hr_work_scheme_rules', 'overtime_start_time')) {
                    $table->dropColumn('overtime_start_time');
                }
            });
        }

        if (Schema::hasTable('hr_employee_profiles')) {
            Schema::table('hr_employee_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('hr_employee_profiles', 'weekly_off_days')) {
                    $table->dropColumn('weekly_off_days');
                }
            });
        }
    }
};

