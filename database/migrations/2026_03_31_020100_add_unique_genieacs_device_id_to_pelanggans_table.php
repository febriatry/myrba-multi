<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pelanggans')) {
            return;
        }
        if (! Schema::hasColumn('pelanggans', 'genieacs_device_id')) {
            return;
        }

        Schema::table('pelanggans', function (Blueprint $table) {
            try {
                $table->unique('genieacs_device_id', 'pelanggans_genieacs_device_id_unique');
            } catch (\Throwable $e) {
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pelanggans')) {
            return;
        }
        if (! Schema::hasColumn('pelanggans', 'genieacs_device_id')) {
            return;
        }

        Schema::table('pelanggans', function (Blueprint $table) {
            $table->dropUnique('pelanggans_genieacs_device_id_unique');
        });
    }
};
