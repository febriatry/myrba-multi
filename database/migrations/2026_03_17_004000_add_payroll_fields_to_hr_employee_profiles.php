<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_employee_profiles')) {
            return;
        }

        Schema::table('hr_employee_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_employee_profiles', 'salary_type')) {
                $table->string('salary_type', 20)->default('monthly')->after('joined_at');
            }
            if (!Schema::hasColumn('hr_employee_profiles', 'monthly_salary')) {
                $table->unsignedBigInteger('monthly_salary')->default(0)->after('salary_type');
            }
            if (!Schema::hasColumn('hr_employee_profiles', 'daily_salary')) {
                $table->unsignedBigInteger('daily_salary')->default(0)->after('monthly_salary');
            }
            if (!Schema::hasColumn('hr_employee_profiles', 'overtime_rate_per_hour')) {
                $table->unsignedBigInteger('overtime_rate_per_hour')->default(0)->after('daily_salary');
            }
            if (!Schema::hasColumn('hr_employee_profiles', 'operational_daily_rate')) {
                $table->unsignedBigInteger('operational_daily_rate')->default(0)->after('overtime_rate_per_hour');
            }
            if (!Schema::hasColumn('hr_employee_profiles', 'mandatory_deduction_type')) {
                $table->string('mandatory_deduction_type', 20)->default('fixed')->after('operational_daily_rate');
            }
            if (!Schema::hasColumn('hr_employee_profiles', 'mandatory_deduction_value')) {
                $table->unsignedBigInteger('mandatory_deduction_value')->default(0)->after('mandatory_deduction_type');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hr_employee_profiles')) {
            return;
        }

        Schema::table('hr_employee_profiles', function (Blueprint $table) {
            foreach ([
                'salary_type',
                'monthly_salary',
                'daily_salary',
                'overtime_rate_per_hour',
                'operational_daily_rate',
                'mandatory_deduction_type',
                'mandatory_deduction_value',
            ] as $col) {
                if (Schema::hasColumn('hr_employee_profiles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

