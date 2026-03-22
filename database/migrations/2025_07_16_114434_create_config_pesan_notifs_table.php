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
        Schema::create('config_pesan_notif', function (Blueprint $table) {
            $table->id();
            $table->text('pesan_notif_pendaftaran');
			$table->text('pesan_notif_tagihan');
			$table->text('pesan_notif_pembayaran');
			$table->text('pesan_notif_kirim_invoice');
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
        Schema::dropIfExists('config_pesan_notif');
    }
};
