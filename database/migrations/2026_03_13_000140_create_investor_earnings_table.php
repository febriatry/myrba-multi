<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('investor_earnings')) {
            Schema::create('investor_earnings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('rule_id')->nullable();
                $table->unsignedBigInteger('pelanggan_id')->nullable();
                $table->unsignedBigInteger('tagihan_id')->nullable();
                $table->string('periode', 7)->nullable();
                $table->decimal('amount', 18, 2);
                $table->timestamps();
                $table->unique(['user_id', 'rule_id', 'tagihan_id'], 'investor_earnings_unique');
                $table->index(['user_id', 'periode']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_earnings');
    }
};

