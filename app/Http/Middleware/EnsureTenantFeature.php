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
            'whatsapp' => 'WhatsApp',
            'payment_gateway' => 'Payment Gateway',
            'inventory' => 'Inventory',
            'hr' => 'HR',
            'olt' => 'OLT',
            'audit' => 'Audit',
        ];
        $label = $labels[$featureKey] ?? $featureKey;

        $defaultEnabled = in_array($featureKey, ['olt', 'audit'], true);
        TenantEntitlementService::ensureFeature($featureKey, $label, $defaultEnabled);

        return $next($request);
    }
}
