<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wa_message_status_logs', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->index();
            $table->string('recipient_id')->nullable()->index();
            $table->string('status')->index();
            $table->string('type')->nullable();
            $table->timestamp('status_at')->nullable();
            $table->json('errors')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['message_id', 'status', 'status_at'], 'wa_msg_status_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('wa_message_status_logs');
    }
};
