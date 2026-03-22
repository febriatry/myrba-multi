<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('secret_ppps')) {
            Schema::create('secret_ppps', function (Blueprint $table) {
                $table->id();
                $table->string('username', 100)->index();
                $table->string('password', 255)->nullable();
                $table->string('service', 50)->nullable();
                $table->string('profile', 100)->nullable();
                $table->string('last_logout', 50)->nullable();
                $table->string('komentar', 255)->nullable();
                $table->string('status', 20)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('secret_ppps');
    }
};

