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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->char('session_id', 36);
            $table->string('name', 255);
            $table->bigInteger('phonebook_id');
            $table->string('message_type', 255);
            $table->longText('message');
            $table->enum('status', ['paused', 'completed', 'waiting', 'processing']);
            $table->integer('delay')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
