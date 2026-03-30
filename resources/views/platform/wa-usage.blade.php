@extends('layouts.app')

@section('title', 'WA Usage (Owner)')

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>WA Usage (Owner)</h3>
                    <p class="text-subtitle text-muted">Hanya menghitung pesan WA tenant yang menggunakan WA milik owner.</p>
                </div>
                <div class="col-12 col-md-4 order-md-2 order-first text-md-end">
                    <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card mb-3">
            <div class="card-body d-flex gap-2 flex-wrap align-items-end">
                <form method="GET" action="{{ route('platform.wa-usage.index') }}" class="d-flex gap-2">
                    <input type="text" name="month" class="form-control" style="max-width: 140px" value="{{ $month }}" placeholder="YYYY-MM">
                    <button class="btn btn-outline-primary">Filter</button>
                </form>
                <div class="ms-auto">
                    <div class="text-muted">Total Sent</div>
                    <div class="fs-3 fw-bold">{{ (int) $total }}</div>
                </div>
                <div>
                    <div class="text-muted">Total Tagihan</div>
                    <div class="fs-3 fw-bold">{{ rupiah((float) ($totalAmount ?? 0)) }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Paket</th>
                                <th class="text-end">WA Sent</th>
                                <th class="text-end">Gratis</th>
                                <th class="text-end">Billable</th>
                                <th class="text-end">Harga / Pesan</th>
                                <th class="text-end">Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byTenant as $r)
                                <tr>
                                    <td>{{ $r->tenant_code ?? ('Tenant #' . (int) $r->tenant_id) }}</td>
                                    <td>{{ $r->plan_name ?? '-' }}</td>
                                    <td class="text-end">{{ (int) ($r->sent ?? 0) }}</td>
                                    <td class="text-end">{{ (int) ($r->free ?? 0) }}</td>
                                    <td class="text-end">{{ (int) ($r->billable ?? 0) }}</td>
                                    <td class="text-end">{{ rupiah((float) ($r->price ?? 0)) }}</td>
                                    <td class="text-end fw-bold">{{ rupiah((float) ($r->amount ?? 0)) }}</td>
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
