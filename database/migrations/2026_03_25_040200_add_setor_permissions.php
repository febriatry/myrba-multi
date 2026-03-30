<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('permissions')) {
            return;
        }

        $perms = [
            'setor view',
            'setor create',
            'setor approve',
            'setor export pdf',
        ];

        foreach ($perms as $p) {
            $exists = DB::table('permissions')->where('name', $p)->where('guard_name', 'web')->exists();
            if ($exists) {
                continue;
            }
            DB::table('permissions')->insert([
                'name' => $p,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('permissions')) {
            return;
        }
        DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', ['setor view', 'setor create', 'setor approve', 'setor export pdf'])
            ->delete();
    }
};

