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
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 36)->primary(); // Menggunakan string sebagai ID utama
            $table->string('session_name', 255);
            $table->string('whatsapp_number')->nullable();
            $table->enum('status', ['CONNECTED', 'STOPPED']);
            $table->text('webhook')->nullable();
            $table->string('api_key');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('is_aktif', ['Yes', 'No'])->default('No');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
