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
                    <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary">Paket</a>
                    <a href="{{ route('platform.wa-usage.index') }}" class="btn btn-outline-secondary">WA Usage</a>
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
                <div class="ms-auto">
                    <div class="text-muted">WA Sent (Owner)</div>
                    <div class="fs-3 fw-bold">{{ (int) ($waOwnerTotalSent ?? 0) }}</div>
                </div>
                <div>
                    <div class="text-muted">Billable</div>
                    <div class="fs-3 fw-bold">{{ (int) ($waOwnerTotalBillable ?? 0) }}</div>
                </div>
                <div>
                    <div class="text-muted">Total Tagihan</div>
                    <div class="fs-3 fw-bold">{{ rupiah((float) ($waOwnerTotalAmount ?? 0)) }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ringkasan per Paket</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Paket</th>
                                <th class="text-end">Tenant</th>
                                <th class="text-end">WA Sent</th>
                                <th class="text-end">Gratis/Tenant</th>
                                <th class="text-end">Billable</th>
                                <th class="text-end">Harga/Pesan</th>
                                <th class="text-end">Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($planRows ?? []) as $r)
                                <tr>
                                    <td class="fw-bold">{{ $r['plan_name'] ?? '-' }}</td>
                                    <td class="text-end">{{ (int) ($r['tenant_count'] ?? 0) }}</td>
                                    <td class="text-end">{{ (int) ($r['wa_sent'] ?? 0) }}</td>
                                    <td class="text-end">{{ (int) ($r['wa_free'] ?? 0) }}</td>
                                    <td class="text-end">{{ (int) ($r['wa_billable'] ?? 0) }}</td>
                                    <td class="text-end">{{ rupiah((float) ($r['wa_price'] ?? 0)) }}</td>
                                    <td class="text-end fw-bold">{{ rupiah((float) ($r['amount'] ?? 0)) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection

