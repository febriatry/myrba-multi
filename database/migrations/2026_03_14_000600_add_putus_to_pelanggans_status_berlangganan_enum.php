<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pelanggans')) {
            return;
        }

        DB::statement("ALTER TABLE pelanggans MODIFY status_berlangganan ENUM('Aktif','Non Aktif','Menunggu','Tunggakan','Putus')");
    }

    public function down(): void
    {
        if (!Schema::hasTable('pelanggans')) {
            return;
        }

        DB::statement("ALTER TABLE pelanggans MODIFY status_berlangganan ENUM('Aktif','Non Aktif','Menunggu','Tunggakan')");
    }
};

