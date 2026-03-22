<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasTenantScope
{
    protected static function bootHasTenantScope()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            try {
                $user = auth()->user();
            } catch (\Throwable $e) {
                $user = null;
            }

            if (!$user || !isset($user->tenant_id)) {
                return;
            }

            $tenantId = (int) $user->tenant_id;
            if ($tenantId > 0) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
                return;
            }

            $builder->whereRaw('1 = 0');
        });
    }
}

