<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pelanggan_request_materials')) {
            Schema::create('pelanggan_request_materials', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pelanggan_id');
                $table->unsignedBigInteger('barang_id');
                $table->string('owner_type', 20)->default('office');
                $table->unsignedBigInteger('owner_user_id')->nullable();
                $table->integer('qty');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['pelanggan_id']);
                $table->index(['owner_type', 'owner_user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggan_request_materials');
    }
};

