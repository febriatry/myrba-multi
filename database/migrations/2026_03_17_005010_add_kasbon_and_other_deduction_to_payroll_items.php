<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_payroll_items')) {
            return;
        }

        Schema::table('hr_payroll_items', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_payroll_items', 'other_deduction_amount')) {
                $table->unsignedBigInteger('other_deduction_amount')->default(0)->after('sanction_deduction_amount');
            }
            if (!Schema::hasColumn('hr_payroll_items', 'kasbon_deduction_amount')) {
                $table->unsignedBigInteger('kasbon_deduction_amount')->default(0)->after('other_deduction_amount');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hr_payroll_items')) {
            return;
        }

        Schema::table('hr_payroll_items', function (Blueprint $table) {
            if (Schema::hasColumn('hr_payroll_items', 'kasbon_deduction_amount')) {
                $table->dropColumn('kasbon_deduction_amount');
            }
            if (Schema::hasColumn('hr_payroll_items', 'other_deduction_amount')) {
                $table->dropColumn('other_deduction_amount');
            }
        });
    }
};

