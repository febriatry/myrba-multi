<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('barang_owner_stocks')) {
            Schema::create('barang_owner_stocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('barang_id');
                $table->string('owner_type', 20);
                $table->unsignedBigInteger('owner_user_id')->nullable();
                $table->integer('qty')->default(0);
                $table->timestamps();
                $table->unique(['barang_id', 'owner_type', 'owner_user_id'], 'barang_owner_unique');
                $table->index(['owner_type', 'owner_user_id']);
            });
        }

        if (Schema::hasTable('barang')) {
            $rows = DB::table('barang')->select('id', 'stock')->get();
            foreach ($rows as $row) {
                $qty = (int) ($row->stock ?? 0);
                DB::table('barang_owner_stocks')->updateOrInsert(
                    ['barang_id' => (int) $row->id, 'owner_type' => 'office', 'owner_user_id' => null],
                    ['qty' => $qty, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_owner_stocks');
    }
};

