<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('profile_pppoes')) {
            Schema::create('profile_pppoes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('local', 50)->nullable();
                $table->string('remote', 50)->nullable();
                $table->string('limit', 50)->nullable();
                $table->string('parent', 100)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_pppoes');
    }
};

