@extends('layouts.app')

@section('title', 'Tenant Dashboard')

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>Tenant Dashboard</h3>
                    <p class="text-subtitle text-muted">Statistik paket dan kuota tenant.</p>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        @php
            $planQuota = is_array($tenant?->plan?->quota_json) ? $tenant->plan->quota_json : [];
            $tenantQuota = is_array($tenant?->quota_json) ? $tenant->quota_json : [];
            $quota = array_merge($planQuota, $tenantQuota);
            $maxUsers = isset($quota['max_users']) && is_numeric($quota['max_users']) ? (int) $quota['max_users'] : null;
            $maxPelanggan = isset($quota['max_pelanggans']) && is_numeric($quota['max_pelanggans']) ? (int) $quota['max_pelanggans'] : null;
            $maxWaMessages = isset($quota['max_wa_messages_monthly']) && is_numeric($quota['max_wa_messages_monthly']) ? (int) $quota['max_wa_messages_monthly'] : null;
            $waEnabled = \App\Services\TenantEntitlementService::featureEnabled('whatsapp', false);
        @endphp

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Tenant</div>
                        <div class="fw-bold">{{ $tenant?->name ?? '-' }}</div>
                        <div class="text-muted mt-2">Paket</div>
                        <div class="fw-bold">{{ $tenant?->plan?->name ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">User</div>
                        <div class="fs-3 fw-bold">{{ (int) $userCount }}{{ $maxUsers ? ' / ' . $maxUsers : '' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Pelanggan</div>
                        <div class="fs-3 fw-bold">{{ (int) $pelangganCount }}{{ $maxPelanggan ? ' / ' . $maxPelanggan : '' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">WA Sent ({{ $waMonth }})</div>
                        <div class="fs-3 fw-bold">{{ $waEnabled ? ((int) $waSentCount) : '-' }}{{ $waEnabled && $maxWaMessages ? ' / ' . $maxWaMessages : '' }}</div>
                        @if ($waEnabled)
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                <a href="{{ route('tenant.wa.settings') }}" class="btn btn-outline-primary btn-sm">WA Settings</a>
                                <a href="{{ route('tenant.wa.report') }}" class="btn btn-outline-primary btn-sm">WA Report</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
