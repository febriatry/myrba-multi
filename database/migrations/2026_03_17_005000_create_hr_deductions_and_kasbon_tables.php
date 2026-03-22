<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_deductions')) {
            Schema::create('hr_deductions', function (Blueprint $table) {
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

        if (!Schema::hasTable('hr_kasbons')) {
            Schema::create('hr_kasbons', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->date('date')->index();
                $table->unsignedBigInteger('amount');
                $table->unsignedBigInteger('remaining_amount')->default(0);
                $table->string('status', 20)->default('open')->index();
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('finance_pengeluaran_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hr_kasbon_repayments')) {
            Schema::create('hr_kasbon_repayments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kasbon_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->date('date')->index();
                $table->unsignedBigInteger('amount');
                $table->string('source', 20)->default('payroll')->index();
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('finance_pemasukan_id')->nullable()->index();
                $table->unsignedBigInteger('payroll_period_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_kasbon_repayments');
        Schema::dropIfExists('hr_kasbons');
        Schema::dropIfExists('hr_deductions');
    }
};

