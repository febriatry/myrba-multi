<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_payroll_periods')) {
            Schema::create('hr_payroll_periods', function (Blueprint $table) {
                $table->id();
                $table->date('period_start')->index();
                $table->date('period_end')->index();
                $table->string('label', 50)->index();
                $table->string('status', 20)->default('draft')->index();
                $table->unsignedBigInteger('generated_by')->nullable();
                $table->dateTime('generated_at')->nullable();
                $table->timestamps();
                $table->unique(['period_start', 'period_end'], 'hr_payroll_period_unique');
            });
        }

        if (!Schema::hasTable('hr_payroll_items')) {
            Schema::create('hr_payroll_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('period_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedInteger('present_days')->default(0);
                $table->unsignedInteger('work_minutes')->default(0);
                $table->unsignedInteger('overtime_minutes')->default(0);
                $table->unsignedBigInteger('base_amount')->default(0);
                $table->unsignedBigInteger('overtime_amount')->default(0);
                $table->unsignedBigInteger('operational_amount')->default(0);
                $table->unsignedBigInteger('mandatory_deduction_amount')->default(0);
                $table->unsignedBigInteger('sanction_deduction_amount')->default(0);
                $table->unsignedBigInteger('total_amount')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->unique(['period_id', 'user_id'], 'hr_payroll_item_period_user_unique');
            });
        }

        if (!Schema::hasTable('hr_operational_dailies')) {
            Schema::create('hr_operational_dailies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->date('date')->index();
                $table->unsignedBigInteger('amount');
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hr_sanctions')) {
            Schema::create('hr_sanctions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->date('date')->index();
                $table->unsignedBigInteger('amount');
                $table->string('type', 50)->nullable();
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_sanctions');
        Schema::dropIfExists('hr_operational_dailies');
        Schema::dropIfExists('hr_payroll_items');
        Schema::dropIfExists('hr_payroll_periods');
    }
};

