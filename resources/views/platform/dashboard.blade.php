@extends('layouts.app')

@section('title', 'Platform Dashboard')

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>Platform Dashboard</h3>
                    <p class="text-subtitle text-muted">Kelola paket, tenant, fitur, kuota, dan laporan agregat.</p>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Total Tenant</div>
                        <div class="fs-3 fw-bold">{{ (int) $tenantCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Tenant Aktif</div>
                        <div class="fs-3 fw-bold">{{ (int) $tenantActiveCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Tenant Suspend</div>
                        <div class="fs-3 fw-bold">{{ (int) $tenantSuspendedCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Total Paket</div>
                        <div class="fs-3 fw-bold">{{ (int) $planCount }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-0">
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Total User (semua tenant)</div>
                        <div class="fs-3 fw-bold">{{ (int) $userCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">WA Sent ({{ $month }})</div>
                        <div class="fs-3 fw-bold">{{ (int) $totalWaSent }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Estimasi Tagihan WA ({{ $month }})</div>
                        <div class="fs-3 fw-bold">{{ $estimatedBillingTotal > 0 ? number_format($estimatedBillingTotal, 2, ',', '.') : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body d-flex gap-2 flex-wrap align-items-end">
                <a href="{{ route('platform.tenants.index') }}" class="btn btn-primary">Kelola Tenant</a>
                <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-primary">Kelola Paket</a>
                <form method="GET" action="{{ route('platform.dashboard') }}" class="ms-auto d-flex gap-2">
                    <input type="text" name="month" class="form-control" style="max-width: 140px" value="{{ $month }}" placeholder="YYYY-MM">
                    <button class="btn btn-outline-secondary">Filter WA</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Status</th>
                                <th>Paket</th>
                                <th>WA</th>
                                <th>WA Sent</th>
                                <th>Kuota WA</th>
                                <th>Harga/Pesan</th>
                                <th>Estimasi</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tenants as $t)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $t['name'] }}</div>
                                        <div class="text-muted">{{ $t['code'] }}</div>
                                    </td>
                                    <td>{{ $t['status'] }}</td>
                                    <td>{{ $t['plan_name'] }} ({{ $t['plan_code'] }})</td>
                                    <td>{{ $t['wa_enabled'] ? 'On' : 'Off' }}</td>
                                    <td>{{ (int) $t['wa_sent'] }}</td>
                                    <td>{{ $t['wa_max_monthly'] !== null ? (int) $t['wa_max_monthly'] : '-' }}</td>
                                    <td>{{ $t['wa_price'] > 0 ? number_format($t['wa_price'], 2, ',', '.') : '-' }}</td>
                                    <td>{{ $t['wa_estimated_total'] > 0 ? number_format($t['wa_estimated_total'], 2, ',', '.') : '-' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('platform.tenants.edit', $t['id']) }}" class="btn btn-sm btn-outline-primary">Atur</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
