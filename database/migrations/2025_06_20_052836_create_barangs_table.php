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
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 50);
			$table->string('nama_barang', 255);
			$table->foreignId('unit_satuan_id')->constrained('unit_satuan')->restrictOnUpdate()->restrictOnDelete();
			$table->foreignId('kategori_barang_id')->constrained('kategori_barang')->restrictOnUpdate()->restrictOnDelete();
			$table->text('deskripsi_barang')->nullable();
			$table->string('photo_barang')->nullable();
			$table->integer('stock')->nullable();
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
        Schema::dropIfExists('barangs');
    }
};
