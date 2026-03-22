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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Who did it
            $table->string('user_name')->nullable(); // Snapshot of name
            $table->string('action'); // created, updated, deleted, login, logout, etc.
            $table->string('description')->nullable();
            $table->nullableMorphs('subject'); // subject_type, subject_id
            $table->json('properties')->nullable(); // Old/New values
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // Index for faster queries
            $table->index('user_id');
            $table->index('subject_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
