<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pelanggan_fcm_tokens')) {
            return;
        }

        Schema::create('pelanggan_fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggans')->cascadeOnDelete();
            $table->string('no_layanan', 50)->nullable()->index();
            $table->text('token')->unique();
            $table->string('platform', 20)->default('android');
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['pelanggan_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggan_fcm_tokens');
    }
};

