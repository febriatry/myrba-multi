<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('investor_share_rules')) {
            Schema::create('investor_share_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('rule_type', 30);
                $table->unsignedBigInteger('coverage_area_id')->nullable();
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('amount_type', 20);
                $table->decimal('amount_value', 18, 2);
                $table->string('is_aktif', 5)->default('Yes');
                $table->timestamps();
                $table->index(['user_id', 'rule_type', 'is_aktif']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_share_rules');
    }
};

