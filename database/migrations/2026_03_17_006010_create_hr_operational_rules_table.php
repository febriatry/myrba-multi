<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hr_operational_rules')) {
            return;
        }

        Schema::create('hr_operational_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->date('date')->nullable()->index();
            $table->unsignedTinyInteger('day_of_week')->nullable()->index();
            $table->unsignedBigInteger('amount');
            $table->string('is_active', 5)->default('Yes')->index();
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'date', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_operational_rules');
    }
};

