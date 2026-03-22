<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('investor_share_rule_pelanggans')) {
            Schema::create('investor_share_rule_pelanggans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rule_id');
                $table->unsignedBigInteger('pelanggan_id');
                $table->string('is_included', 5)->default('Yes');
                $table->timestamps();
                $table->unique(['rule_id', 'pelanggan_id'], 'investor_rule_pelanggan_unique');
                $table->index(['rule_id', 'is_included']);
                $table->index('pelanggan_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_share_rule_pelanggans');
    }
};

