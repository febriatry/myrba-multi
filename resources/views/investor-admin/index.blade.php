@extends('layouts.app')

@section('title', __('Dashboard Investor & Mitra (Admin)'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Dashboard Investor & Mitra (Admin)') }}</h3>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Investor & Mitra (Admin)') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <div class="card mb-3">
                <div class="card-body">
                    <form method="get" action="{{ route('investor-admin.index') }}" class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Periode') }}</label>
                            <select name="period" class="form-control">
                                @foreach ($periodOptions as $p)
                                    <option value="{{ $p }}" @selected($p === $period)>{{ $p }}</option>
                                @endforeach
                            </select>
                            @if (!empty($minStartPeriod))
                                <div class="text-muted mt-1">{{ __('Data rule mulai: :p', ['p' => $minStartPeriod]) }}</div>
                            @endif
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Total User') }}</h6>
                            <h4 class="mb-0">{{ number_format($summary['total_users'] ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Total Saldo') }}</h6>
                            <h4 class="mb-0">{{ number_format((float) ($summary['total_balance'] ?? 0), 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Bagi Hasil Periode') }}</h6>
                            <h4 class="mb-0">{{ number_format((float) ($summary['earning_period_amount'] ?? 0), 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Payout Pending') }}</h6>
                            <h4 class="mb-0">{{ number_format((float) ($summary['payout_pending_amount'] ?? 0), 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('Saldo') }}</th>
                                    <th>{{ __('Rule Aktif/Total') }}</th>
                                    <th>{{ __('Bagi Hasil (Periode)') }}</th>
                                    <th>{{ __('Bagi Hasil (Total)') }}</th>
                                    <th>{{ __('Payout Pending') }}</th>
                                    <th>{{ __('Payout Approved (Periode)') }}</th>
                                    <th>{{ __('Last Activity') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $r)
                                    <tr>
                                        <td>
                                            {{ $r['name'] ?? '-' }}
                                            <div class="text-muted">{{ $r['email'] ?? '' }}</div>
                                        </td>
                                        <td>{{ number_format((float) ($r['balance'] ?? 0), 0, ',', '.') }}</td>
                                        <td>{{ (int) ($r['rules_active'] ?? 0) }}/{{ (int) ($r['rules_total'] ?? 0) }}</td>
                                        <td>{{ number_format((float) ($r['earning_period_amount'] ?? 0), 0, ',', '.') }}<div class="text-muted">{{ number_format((int) ($r['earning_period_count'] ?? 0)) }} tx</div></td>
                                        <td>{{ number_format((float) ($r['earning_total_amount'] ?? 0), 0, ',', '.') }}</td>
                                        <td>{{ number_format((float) ($r['payout_pending_amount'] ?? 0), 0, ',', '.') }}<div class="text-muted">{{ number_format((int) ($r['payout_pending_count'] ?? 0)) }} req</div></td>
                                        <td>{{ number_format((float) ($r['payout_approved_period_amount'] ?? 0), 0, ',', '.') }}</td>
                                        <td>{{ $r['last_activity_at'] ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">{{ __('Belum ada data investor/mitra.') }}</td>
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

