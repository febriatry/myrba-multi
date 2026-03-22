<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('active_ppps')) {
            Schema::create('active_ppps', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->index();
                $table->string('service', 50)->nullable();
                $table->string('caller_id', 100)->nullable();
                $table->string('ip_address', 50)->nullable();
                $table->string('uptime', 50)->nullable();
                $table->string('komentar', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('active_ppps');
    }
};

