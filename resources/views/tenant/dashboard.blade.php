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
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Tenant</div>
                        <div class="fw-bold">{{ $tenant?->name ?? '-' }}</div>
                        <div class="text-muted mt-2">Status</div>
                        <div class="fw-bold">{{ $tenant?->status ?? '-' }}</div>
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
                        @if ($maxUsers)
                            <div class="text-muted">Sisa: {{ max(0, (int) $maxUsers - (int) $userCount) }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Pelanggan</div>
                        <div class="fs-3 fw-bold">{{ (int) $pelangganCount }}{{ $maxPelanggan ? ' / ' . $maxPelanggan : '' }}</div>
                        @if ($maxPelanggan)
                            <div class="text-muted">Sisa: {{ max(0, (int) $maxPelanggan - (int) $pelangganCount) }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">WA Sent ({{ $waMonth }})</div>
                        <div class="fs-3 fw-bold">{{ $waEnabled ? ((int) $waSentCount) : '-' }}{{ $waEnabled && $maxWaMessages ? ' / ' . $maxWaMessages : '' }}</div>
                        @if ($waEnabled && $maxWaMessages)
                            <div class="text-muted">Sisa: {{ (int) ($waRemaining ?? 0) }}</div>
                        @endif
                        <div class="text-muted mt-2">Mode</div>
                        <div class="fw-bold">{{ $waEnabled ? ($waProviderLabel ?? '-') : '-' }}</div>
                        @if ($waEnabled)
                            <div class="text-muted mt-2">Breakdown</div>
                            <div class="fw-bold">Owner: {{ (int) ($waSentOwner ?? 0) }} | Tenant: {{ (int) ($waSentTenant ?? 0) }}</div>
                        @endif
                        @if ($waEnabled && (float) ($waPrice ?? 0) > 0)
                            <div class="text-muted mt-2">Estimasi biaya (Owner WA)</div>
                            <div class="fw-bold">{{ rupiah((float) ($waEstimatedCost ?? 0)) }}</div>
                            <div class="text-muted">Billable Owner: {{ (int) ($waBillableOwner ?? 0) }} (Gratis {{ (int) ($waFreeMonthly ?? 0) }})</div>
                        @endif
                        @if ($waEnabled)
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                <a href="{{ route('tenant.wa.settings') }}" class="btn btn-outline-primary btn-sm">WA Settings</a>
                                <a href="{{ route('tenant.wa.report') }}" class="btn btn-outline-primary btn-sm">WA Report</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Payment Gateway</div>
                        <div class="fs-4 fw-bold">{{ $paymentEnabled ? 'Aktif' : 'Nonaktif' }}</div>
                        <div class="text-muted mt-2">Mode</div>
                        <div class="fw-bold">{{ $paymentEnabled ? ($tripayProviderLabel ?? '-') : '-' }}</div>
                        @if ($paymentEnabled)
                            <div class="mt-2">
                                <a href="{{ route('tenant.payment.settings') }}" class="btn btn-outline-primary btn-sm">Payment Settings</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
