<?php

namespace App\Services;

use App\Models\Tenant;

class TenantEntitlementService
{
    public static function currentTenant(): ?Tenant
    {
        $user = auth()->user();
        if (!$user || !isset($user->tenant_id)) {
            return null;
        }

        return Tenant::query()->with('plan')->find((int) $user->tenant_id);
    }

    public static function quota(string $key): ?int
    {
        $tenant = self::currentTenant();
        if (!$tenant) {
            return null;
        }

        $planQuota = is_array($tenant->plan?->quota_json) ? $tenant->plan->quota_json : [];
        $tenantQuota = is_array($tenant->quota_json) ? $tenant->quota_json : [];
        $merged = array_merge($planQuota, $tenantQuota);

        if (!array_key_exists($key, $merged)) {
            return null;
        }

        $val = $merged[$key];
        if ($val === null || $val === '') {
            return null;
        }

        if (!is_numeric($val)) {
            return null;
        }

        $val = (int) $val;
        return $val > 0 ? $val : null;
    }

    public static function featureEnabled(string $key, bool $default = false): bool
    {
        $tenant = self::currentTenant();
        if (!$tenant) {
            return $default;
        }

        return self::featureEnabledForTenant($tenant, $key, $default);
    }

    public static function featureEnabledForTenantId(int $tenantId, string $key, bool $default = false): bool
    {
        if ($tenantId < 1) {
            return $default;
        }
        $tenant = Tenant::query()->with('plan')->find($tenantId);
        if (!$tenant) {
            return $default;
        }
        return self::featureEnabledForTenant($tenant, $key, $default);
    }

    private static function featureEnabledForTenant(Tenant $tenant, string $key, bool $default): bool
    {
        $planFeatures = is_array($tenant->plan?->features_json) ? $tenant->plan->features_json : [];
        $tenantFeatures = is_array($tenant->features_json) ? $tenant->features_json : [];
        $merged = array_merge($planFeatures, $tenantFeatures);

        if (!array_key_exists($key, $merged)) {
            return $default;
        }

        $val = $merged[$key];
        if (is_bool($val)) {
            return $val;
        }
        if (is_numeric($val)) {
            return ((int) $val) === 1;
        }
        $text = strtolower(trim((string) $val));
        if (in_array($text, ['1', 'true', 'yes', 'on', 'enabled'], true)) {
            return true;
        }
        if (in_array($text, ['0', 'false', 'no', 'off', 'disabled'], true)) {
            return false;
        }
        return $default;
    }

    public static function ensureFeature(string $key, string $label)
    {
        if (!self::featureEnabled($key, false)) {
            abort(403, 'Fitur tidak tersedia: ' . $label);
        }
    }

    public static function ensureQuota(string $key, int $current, int $delta, string $label)
    {
        $limit = self::quota($key);
        if ($limit === null) {
            return;
        }

        if (($current + $delta) > $limit) {
            abort(403, 'Kuota ' . $label . ' telah habis. Maksimal: ' . $limit);
        }
    }
}
