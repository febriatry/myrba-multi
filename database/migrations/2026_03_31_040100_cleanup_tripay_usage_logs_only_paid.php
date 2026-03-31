<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tripay_usage_logs')) {
            return;
        }
        DB::table('tripay_usage_logs')->where(function ($q) {
            $q->whereNull('status')->orWhere('status', '<>', 'PAID');
        })->delete();
    }

    public function down(): void {}
};
