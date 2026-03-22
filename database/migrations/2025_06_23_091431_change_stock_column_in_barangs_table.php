<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pertama, ubah semua nilai NULL yang sudah ada menjadi 0
        DB::table('barang')->whereNull('stock')->update(['stock' => 0]);

        // Kemudian, ubah definisi kolom
        Schema::table('barang', function (Blueprint $table) {
            $table->integer('stock')->default(0)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            // Kembalikan ke definisi lama jika di-rollback
            $table->integer('stock')->nullable()->change();
        });
    }
};
