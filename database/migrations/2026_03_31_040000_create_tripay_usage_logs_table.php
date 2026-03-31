<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tripay_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('merchant_ref', 80)->index();
            $table->string('tripay_reference', 80)->nullable()->index();
            $table->string('type', 30)->nullable()->index();
            $table->string('method', 50)->nullable();
            $table->string('status', 30)->nullable()->index();
            $table->bigInteger('amount')->default(0);
            $table->string('gateway_mode', 20)->default('owner')->index();
            $table->dateTime('paid_at')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['merchant_ref', 'gateway_mode'], 'tripay_usage_logs_merchant_mode_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tripay_usage_logs');
    }
};
