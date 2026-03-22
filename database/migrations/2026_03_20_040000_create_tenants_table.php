<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name', 150);
            $table->string('code', 50)->unique();
            $table->string('status', 20)->default('active');
            $table->json('features_json')->nullable();
            $table->json('quota_json')->nullable();
            $table->timestamps();
        });

        DB::table('tenants')->insertOrIgnore([
            'id' => 1,
            'name' => 'Default Tenant',
            'code' => 'default',
            'status' => 'active',
            'features_json' => null,
            'quota_json' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

