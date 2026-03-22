<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultPlanEntitlementsSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('tenant_plans')) {
            return;
        }

        DB::table('tenant_plans')->where('id', 1)->update([
            'features_json' => json_encode([
                'whatsapp' => true,
                'payment_gateway' => true,
                'inventory' => true,
                'hr' => true,
            ]),
            'quota_json' => json_encode([
                'wa_price_per_message' => 0,
            ]),
            'updated_at' => now(),
        ]);
    }
}

