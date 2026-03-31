<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $driver = (string) DB::getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        try {
            DB::statement('ALTER TABLE `tenants` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        } catch (\Throwable $e) {
            return;
        }
    }

    public function down(): void {}
};
