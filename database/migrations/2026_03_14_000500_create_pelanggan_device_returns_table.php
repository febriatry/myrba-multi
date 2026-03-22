<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pelanggan_device_returns')) {
            Schema::create('pelanggan_device_returns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pelanggan_id');
                $table->string('status_return', 20);
                $table->json('items')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('transaksi_in_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['pelanggan_id', 'status_return']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggan_device_returns');
    }
};

