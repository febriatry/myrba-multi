<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mikrotik_action_logs')) {
            Schema::create('mikrotik_action_logs', function (Blueprint $table) {
                $table->id();
                $table->string('action', 20);
                $table->unsignedBigInteger('pelanggan_id')->nullable();
                $table->unsignedBigInteger('router_id')->nullable();
                $table->string('mode_user', 20)->nullable();
                $table->string('identity', 150)->nullable();
                $table->string('reason', 150)->nullable();
                $table->enum('status', ['ok', 'failed', 'skipped'])->default('ok');
                $table->text('error_message')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->string('performed_via', 30)->default('web');
                $table->timestamps();

                $table->index(['action', 'status']);
                $table->index(['pelanggan_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotik_action_logs');
    }
};

