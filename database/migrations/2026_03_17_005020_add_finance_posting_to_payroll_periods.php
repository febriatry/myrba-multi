<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_payroll_periods')) {
            return;
        }

        Schema::table('hr_payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_payroll_periods', 'finance_pengeluaran_id')) {
                $table->unsignedBigInteger('finance_pengeluaran_id')->nullable()->after('generated_at')->index();
            }
            if (!Schema::hasColumn('hr_payroll_periods', 'posted_at')) {
                $table->dateTime('posted_at')->nullable()->after('finance_pengeluaran_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hr_payroll_periods')) {
            return;
        }

        Schema::table('hr_payroll_periods', function (Blueprint $table) {
            if (Schema::hasColumn('hr_payroll_periods', 'posted_at')) {
                $table->dropColumn('posted_at');
            }
            if (Schema::hasColumn('hr_payroll_periods', 'finance_pengeluaran_id')) {
                $table->dropColumn('finance_pengeluaran_id');
            }
        });
    }
};

