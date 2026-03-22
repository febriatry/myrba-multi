<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auto_responders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->char('session_id', 36);
            $table->string('keyword', 255);
            $table->enum('type_keyword', ['contains', 'equal'])->default('equal');
            $table->string('message_type', 255);
            $table->longText('message');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('reply_when', ['all', 'group', 'personal'])->default('all');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_responders');
    }
};
