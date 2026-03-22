<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('topups', function (Blueprint $table) {
            $table->id();
            $table->string('no_topup', 100);
            $table->foreignId('pelanggan_id')->constrained('pelanggans')->restrictOnUpdate()->cascadeOnDelete();
            $table->dateTime('tanggal_topup');
            $table->integer('nominal');
            $table->enum('status', ['pending', 'success', 'failed', 'canceled', 'refunded', 'expired']);
            $table->enum('metode', ['manual', 'tripay']);
            $table->string('metode_topup', 255);
            $table->text('payload_tripay')->nullable();
            $table->dateTime('tanggal_callback_tripay')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnUpdate()->nullOnDelete();
            $table->string('bukti_topup', 255)->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->restrictOnUpdate()->nullOnDelete();
            $table->dateTime('tanggal_review')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('topups');
    }
};
