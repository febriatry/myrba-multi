<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tagihans')) {
            Schema::table('tagihans', function (Blueprint $table) {
                if (!Schema::hasColumn('tagihans', 'printed_at')) {
                    $table->timestamp('printed_at')->nullable()->after('tanggal_kirim_notif_wa');
                }
                if (!Schema::hasColumn('tagihans', 'printed_by')) {
                    $table->unsignedBigInteger('printed_by')->nullable()->after('printed_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tagihans')) {
            Schema::table('tagihans', function (Blueprint $table) {
                foreach (['printed_by', 'printed_at'] as $col) {
                    if (Schema::hasColumn('tagihans', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

