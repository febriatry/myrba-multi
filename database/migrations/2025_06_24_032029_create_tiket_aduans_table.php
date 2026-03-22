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
        Schema::create('tiket_aduans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_tiket', 50);
			$table->foreignId('pelanggan_id')->constrained('pelanggans')->restrictOnUpdate()->restrictOnDelete();
			$table->text('deskripsi_aduan');
			$table->dateTime('tanggal_aduan');
			$table->enum('status', ['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan']);
			$table->enum('prioritas', ['Rendah', 'Sedang', 'Tinggi']);
			$table->string('lampiran')->nullable();
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
        Schema::dropIfExists('tiket_aduans');
    }
};
