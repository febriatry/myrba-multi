<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tagihans')) {
            return;
        }
        if (!Schema::hasColumn('tagihans', 'status_bayar')) {
            return;
        }

        DB::statement("ALTER TABLE `tagihans` MODIFY `status_bayar` ENUM('Sudah Bayar','Belum Bayar','Waiting Review','Menunggu setor') NOT NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('tagihans')) {
            return;
        }
        if (!Schema::hasColumn('tagihans', 'status_bayar')) {
            return;
        }

        DB::statement("ALTER TABLE `tagihans` MODIFY `status_bayar` ENUM('Sudah Bayar','Belum Bayar','Waiting Review') NOT NULL");
    }
};

