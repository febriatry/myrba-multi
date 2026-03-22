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
        Schema::create('setting_web', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan', 244);
			$table->string('telepon_perusahaan', 15);
			$table->string('email')->unique();
			$table->string('no_wa', 15);
			$table->text('alamat');
			$table->text('deskripsi_perusahaan');
			$table->string('logo');
			$table->string('url_tripay', 255);
			$table->string('api_key_tripay', 255);
			$table->string('kode_merchant', 255);
			$table->string('private_key', 255);
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
        Schema::dropIfExists('setting_web');
    }
};
