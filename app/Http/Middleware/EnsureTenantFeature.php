<?php

namespace App\Http\Middleware;

use App\Services\TenantEntitlementService;
use Closure;
use Illuminate\Http\Request;

class EnsureTenantFeature
{
    public function handle(Request $request, Closure $next, string $featureKey)
    {
        $labels = [
            'finance' => 'Keuangan',
            'pelanggan' => 'Pelanggan',
            'layanan' => 'Kelola Layanan',
            'network' => 'Network Ops',
            'pppoe' => 'PPPoE',
            'hotspot' => 'Hotspot',
            'investor' => 'Investor',
            'cms' => 'CMS',
            'settings' => 'Settings',
            'whatsapp' => 'WhatsApp',
            'payment_gateway' => 'Payment Gateway',
            'inventory' => 'Inventory',
            'hr' => 'HR',
            'olt' => 'OLT',
            'audit' => 'Audit',
        ];
        $label = $labels[$featureKey] ?? $featureKey;

        TenantEntitlementService::ensureFeature($featureKey, $label, true);

        return $next($request);
    }
}
