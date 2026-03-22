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
        ];
        $label = $labels[$featureKey] ?? $featureKey;

        TenantEntitlementService::ensureFeature($featureKey, $label);

        return $next($request);
    }
}

