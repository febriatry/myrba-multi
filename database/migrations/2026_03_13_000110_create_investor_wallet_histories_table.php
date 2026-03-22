<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('investor_wallet_histories')) {
            Schema::create('investor_wallet_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('type', 20);
                $table->decimal('amount', 18, 2);
                $table->decimal('balance_before', 18, 2);
                $table->decimal('balance_after', 18, 2);
                $table->string('description', 500)->nullable();
                $table->timestamps();
                $table->index(['user_id', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_wallet_histories');
    }
};

