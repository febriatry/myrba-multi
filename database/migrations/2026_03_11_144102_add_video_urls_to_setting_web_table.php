<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('setting_web', function (Blueprint $table) {
            $table->string('video_url_1')->nullable()->after('is_wa_welcome_active');
            $table->string('video_url_2')->nullable()->after('video_url_1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_web', function (Blueprint $table) {
            $table->dropColumn(['video_url_1', 'video_url_2']);
        });
    }
};
