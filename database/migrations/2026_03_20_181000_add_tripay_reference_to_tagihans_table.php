<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            if (!Schema::hasColumn('tagihans', 'tripay_reference')) {
                $table->string('tripay_reference', 100)->nullable()->after('payload_tripay');
                $table->index('tripay_reference', 'tagihans_tripay_reference_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            if (Schema::hasColumn('tagihans', 'tripay_reference')) {
                $table->dropIndex('tagihans_tripay_reference_index');
                $table->dropColumn('tripay_reference');
            }
        });
    }
};

