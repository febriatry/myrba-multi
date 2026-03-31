@extends('layouts.app')

@section('title', 'Owner Dashboard')

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>Owner Dashboard</h3>
                    <p class="text-subtitle text-muted">Ringkasan penggunaan fitur yang ditagihkan via owner.</p>
                </div>
                <div class="col-12 col-md-4 order-md-2 order-first text-md-end">
                    <a href="{{ route('platform.settings.index') }}" class="btn btn-outline-secondary">Owner Settings</a>
                    <a href="{{ route('wa-config.index') }}" class="btn btn-outline-secondary">WA Config</a>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card mb-3">
            <div class="card-body d-flex gap-2 flex-wrap align-items-end">
                <form method="GET" action="{{ route('platform.dashboard') }}" class="d-flex gap-2">
                    <input type="text" name="month" class="form-control" style="max-width: 140px" value="{{ $month }}" placeholder="YYYY-MM">
                    <button class="btn btn-outline-primary">Filter</button>
                </form>
                <div class="ms-auto d-flex gap-2 flex-wrap">
                    <a href="{{ route('platform.wa-usage.index', ['month' => $month]) }}" class="btn btn-outline-secondary">Detail WA</a>
                    <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary">Paket</a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">WA Sent (Owner)</div>
                        <div class="fs-3 fw-bold">{{ (int) ($waOwnerTotalSent ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">WA Billable</div>
                        <div class="fs-3 fw-bold">{{ (int) ($waOwnerTotalBillable ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">WA Tagihan</div>
                        <div class="fs-3 fw-bold">{{ rupiah((float) ($waOwnerTotalAmount ?? 0)) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Tripay Paid</div>
                        <div class="fs-3 fw-bold">{{ (int) ($tripayPaidTotal ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Tripay Nominal (Paid)</div>
                        <div class="fs-3 fw-bold">{{ rupiah((float) ($tripayPaidAmount ?? 0)) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">WA Owner per Paket</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Paket</th>
                                        <th class="text-end">Tenant</th>
                                        <th class="text-end">Sent</th>
                                        <th class="text-end">Billable</th>
                                        <th class="text-end">Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse (($planRows ?? []) as $r)
                                        <tr>
                                            <td class="fw-bold">{{ $r['plan_name'] ?? '-' }}</td>
                                            <td class="text-end">{{ (int) ($r['tenant_count'] ?? 0) }}</td>
                                            <td class="text-end">{{ (int) ($r['wa_sent'] ?? 0) }}</td>
                                            <td class="text-end">{{ (int) ($r['wa_billable'] ?? 0) }}</td>
                                            <td class="text-end fw-bold">{{ rupiah((float) ($r['amount'] ?? 0)) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Tripay Owner</h5>
                        <div class="text-muted">{{ $month }}</div>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tripayByPlan" type="button" role="tab">Per Paket</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tripayByTenant" type="button" role="tab">Per Tenant</button>
                            </li>
                        </ul>
                        <div class="tab-content pt-3">
                            <div class="tab-pane fade show active" id="tripayByPlan" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Paket</th>
                                                <th class="text-end">Tenant</th>
                                                <th class="text-end">Paid</th>
                                                <th class="text-end">Nominal Paid</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse (($tripayPlanRows ?? []) as $r)
                                                <tr>
                                                    <td class="fw-bold">{{ $r['plan_name'] ?? '-' }}</td>
                                                    <td class="text-end">{{ (int) ($r['tenant_count'] ?? 0) }}</td>
                                                    <td class="text-end">{{ (int) ($r['paid_total'] ?? 0) }}</td>
                                                    <td class="text-end fw-bold">{{ rupiah((float) ($r['paid_amount'] ?? 0)) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tripayByTenant" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Tenant</th>
                                                <th class="text-end">Paid</th>
                                                <th class="text-end">Nominal Paid</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse (($tripayByTenant ?? []) as $r)
                                                <tr>
                                                    <td class="fw-bold">{{ $r->tenant_code ?? ('Tenant #' . (int) $r->tenant_id) }}</td>
                                                    <td class="text-end">{{ (int) ($r->paid_total ?? 0) }}</td>
                                                    <td class="text-end fw-bold">{{ rupiah((float) ($r->paid_amount ?? 0)) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
