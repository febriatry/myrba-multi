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

        Schema::table('pelanggans', function (Blueprint $table) {
            if (! Schema::hasColumn('pelanggans', 'genieacs_device_id')) {
                $table->string('genieacs_device_id', 128)->nullable()->index();
            }
            if (! Schema::hasColumn('pelanggans', 'genieacs_status_json')) {
                $table->json('genieacs_status_json')->nullable();
            }
            if (! Schema::hasColumn('pelanggans', 'genieacs_last_inform_at')) {
                $table->dateTime('genieacs_last_inform_at')->nullable()->index();
            }
            if (! Schema::hasColumn('pelanggans', 'genieacs_synced_at')) {
                $table->dateTime('genieacs_synced_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pelanggans')) {
            return;
        }

        Schema::table('pelanggans', function (Blueprint $table) {
            $cols = ['genieacs_device_id', 'genieacs_status_json', 'genieacs_last_inform_at', 'genieacs_synced_at'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('pelanggans', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
