<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_web', function (Blueprint $table) {
            $table->enum('is_wa_broadcast_active', ['Yes', 'No'])->default('Yes')->after('private_key');
        });
    }

    public function down(): void
    {
        Schema::table('setting_web', function (Blueprint $table) {
            $table->dropColumn('is_wa_broadcast_active');
        });
    }
};
