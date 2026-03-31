@extends('layouts.app')

@section('title', __('Investor & Mitra'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Investor & Mitra') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Bagi hasil dihitung dari tagihan yang sudah dibayar.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Investor & Mitra') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Saldo') }}</h6>
                            <h4 class="mb-0">{{ number_format($balance, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Pelanggan') }}</h6>
                            <h4 class="mb-0">{{ number_format($summary['total']) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Paid / Unpaid') }}</h6>
                            <h4 class="mb-0">{{ number_format($summary['paid']) }} / {{ number_format($summary['unpaid']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="get" action="{{ route('investor.index') }}" class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Periode') }}</label>
                            <select name="period" class="form-control">
                                @foreach ($periodOptions as $p)
                                    <option value="{{ $p }}" @selected($p === $period)>{{ $p }}</option>
                                @endforeach
                            </select>
                            @if (!empty($minStartPeriod))
                                <div class="text-muted mt-1">{{ __('Perhitungan mulai: :p', ['p' => $minStartPeriod]) }}</div>
                            @endif
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('No Layanan') }}</th>
                                    <th>{{ __('Nama') }}</th>
                                    <th>{{ __('Area') }}</th>
                                    <th>{{ __('Paket') }}</th>
                                    <th>{{ __('Periode') }}</th>
                                    <th>{{ __('Status Bayar') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pelanggans as $p)
                                    @php
                                        $t = $tagihansByPelanggan[(int) $p->id] ?? null;
                                        $status = $t->status_bayar ?? null;
                                        $badge = 'bg-secondary';
                                        if ($status) {
                                            $s = strtolower(trim((string) $status));
                                            if (in_array($s, ['sudah bayar', 'paid', 'lunas'])) $badge = 'bg-success';
                                            if (in_array($s, ['belum bayar', 'unpaid'])) $badge = 'bg-danger';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ formatNoLayananTenant($p->no_layanan, (int) (auth()->user()->tenant_id ?? 0)) }}</td>
                                        <td>{{ $p->nama }}</td>
                                        <td>{{ $p->area_nama ?? '-' }}</td>
                                        <td>{{ $p->paket_nama ?? '-' }}</td>
                                        <td>{{ $t->periode ?? $period }}</td>
                                        <td><span class="badge {{ $badge }}">{{ $status ?? 'Belum Ada Tagihan' }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('Belum ada pelanggan untuk rule investor ini.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
