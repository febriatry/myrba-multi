@extends('layouts.app')

@section('title', 'Laporan Pemakaian WhatsApp')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Laporan Pemakaian WhatsApp</h3>
        <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.wa.report') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label">Bulan (YYYY-MM)</label>
                    <input type="text" name="month" class="form-control" value="{{ $month }}">
                </div>
                <div class="col-12 col-md-4">
                    <button class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Total Pesan (sent)</div>
                    <div class="fs-3 fw-bold">{{ (int) $total }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Harga per Pesan</div>
                    <div class="fs-3 fw-bold">{{ $price > 0 ? number_format($price, 2, ',', '.') : '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Estimasi Tagihan</div>
                    <div class="fs-3 fw-bold">{{ $estimatedTotal > 0 ? number_format($estimatedTotal, 2, ',', '.') : '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byType as $row)
                            <tr>
                                <td>{{ $row->type ?? '-' }}</td>
                                <td>{{ (int) $row->total }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">Belum ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

