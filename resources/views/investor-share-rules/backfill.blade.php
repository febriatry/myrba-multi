@extends('layouts.app')

@section('title', __('Backfill Bagi Hasil'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Backfill Bagi Hasil') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Investor: :user | Rule: :rule | Area: :area | Paket: :paket', [
                            'user' => $rule->user_name ?? '-',
                            'rule' => $rule->rule_type ?? '-',
                            'area' => $rule->area_nama ?? '-',
                            'paket' => $rule->paket_nama ?? '-',
                        ]) }}
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('investor-share-rules.index') }}">{{ __('Aturan Bagi Hasil') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Backfill') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>
            <div class="card">
                <div class="card-body">
                    <form method="post" action="{{ route('investor-share-rules.backfill.run', $rule->id) }}" class="row g-2">
                        @csrf
                        <input type="hidden" name="mode" id="mode" value="backfill">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Dari Periode') }}</label>
                            <select name="from_period" class="form-control" required>
                                @foreach (array_reverse($periodOptions) as $p)
                                    <option value="{{ $p }}" @selected(($defaults['from_period'] ?? '') === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Sampai Periode') }}</label>
                            <select name="to_period" class="form-control" required>
                                @foreach ($periodOptions as $p)
                                    <option value="{{ $p }}" @selected(($defaults['to_period'] ?? '') === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('Limit (opsional)') }}</label>
                            <input type="number" name="limit" class="form-control" min="1" max="20000">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100" onclick="document.getElementById('mode').value='backfill'">{{ __('Backfill') }}</button>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-warning w-100" onclick="document.getElementById('mode').value='recalculate'">{{ __('Recalculate') }}</button>
                        </div>

                        <div class="col-12 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="dry_run" name="dry_run">
                                <label class="form-check-label" for="dry_run">
                                    {{ __('Dry-run (hanya hitung kandidat, tidak kredit saldo)') }}
                                </label>
                            </div>
                            @if (!empty($startPeriod))
                                <div class="text-muted mt-1">{{ __('Perhitungan rule ini mulai: :p', ['p' => $startPeriod]) }}</div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            @php
                $report = session('backfill_report');
            @endphp
            @if (!empty($report))
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="mb-3">{{ __('Ringkasan') }}</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-muted">{{ __('Periode') }}</div>
                                <div>{{ $report['from'] ?? '-' }} - {{ $report['to'] ?? '-' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted">{{ __('Total Paid') }}</div>
                                <div>{{ number_format((int) ($report['total_paid'] ?? 0)) }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted">{{ __('Match Rule') }}</div>
                                <div>{{ number_format((int) ($report['total_matched'] ?? 0)) }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted">{{ __('Estimasi Kredit Baru') }}</div>
                                <div>{{ number_format((int) ($report['estimated_new'] ?? 0)) }}</div>
                            </div>
                        </div>

                        @if (isset($report['processed']))
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <div class="text-muted">{{ __('Diproses') }}</div>
                                    <div>{{ number_format((int) ($report['processed'] ?? 0)) }}</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted">{{ __('Kredit Baru') }}</div>
                                    <div>{{ number_format((int) ($report['credited'] ?? 0)) }}</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted">{{ __('Reversal') }}</div>
                                    <div>{{ number_format((int) ($report['reversed'] ?? 0)) }}</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted">{{ __('Skip') }}</div>
                                    <div>{{ number_format((int) ($report['skipped'] ?? 0)) }}</div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <div class="text-muted">{{ __('Earnings Existing') }}</div>
                                    <div>{{ number_format((int) ($report['existing_earnings'] ?? 0)) }}</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted">{{ __('Delta Saldo') }}</div>
                                    <div>{{ number_format((float) ($report['delta_amount'] ?? 0), 0, ',', '.') }}</div>
                                </div>
                                <div class="col-md-6"></div>
                            </div>
                        @endif

                        @if (!empty($report['samples']) && count($report['samples']) > 0)
                            <h6 class="mt-4">{{ __('Contoh Tagihan Match Rule (max 10)') }}</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>{{ __('No Tagihan') }}</th>
                                            <th>{{ __('Periode') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Total') }}</th>
                                            <th>{{ __('No Layanan') }}</th>
                                            <th>{{ __('Nama') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($report['samples'] as $s)
                                            <tr>
                                                <td>{{ $s->id }}</td>
                                                <td>{{ $s->no_tagihan }}</td>
                                                <td>{{ $s->periode }}</td>
                                                <td>{{ $s->status_bayar }}</td>
                                                <td>{{ number_format((float) ($s->total_bayar ?? 0), 0, ',', '.') }}</td>
                                                <td>{{ isset($s->no_layanan) ? formatNoLayananTenant($s->no_layanan, (int) (auth()->user()->tenant_id ?? 0)) : '-' }}</td>
                                                <td>{{ $s->nama ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
