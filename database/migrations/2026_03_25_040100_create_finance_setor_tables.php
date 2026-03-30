<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finance_setors')) {
            Schema::create('finance_setors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->string('code', 30);
                $table->unsignedBigInteger('depositor_id')->index();
                $table->dateTime('deposited_at')->index();
                $table->string('method', 30)->default('Cash')->index();
                $table->unsignedBigInteger('bank_account_id')->nullable()->index();
                $table->string('status', 20)->default('pending')->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->dateTime('approved_at')->nullable();
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('total_nominal')->default(0);
                $table->unsignedInteger('total_items')->default(0);
                $table->timestamps();

                $table->unique(['tenant_id', 'code'], 'finance_setors_tenant_code_unique');
            });
        }

        if (!Schema::hasTable('finance_setor_items')) {
            Schema::create('finance_setor_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->unsignedBigInteger('setor_id')->index();
                $table->unsignedBigInteger('tagihan_id')->unique();
                $table->unsignedBigInteger('pelanggan_id')->nullable()->index();
                $table->unsignedBigInteger('area_coverage_id')->nullable()->index();
                $table->string('periode', 10)->nullable()->index();
                $table->unsignedBigInteger('nominal')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_setor_items');
        Schema::dropIfExists('finance_setors');
    }
};

