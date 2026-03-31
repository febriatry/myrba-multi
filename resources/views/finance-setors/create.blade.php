@extends('layouts.app')

@section('title', __('Buat Setor'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Buat Setor') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Menampilkan tagihan cash/transfer yang sudah divalidasi dan menunggu setor.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('finance-setors.index') }}">{{ __('Setor') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Buat') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('finance-setors.create') }}" class="row g-2 align-items-end">
                        @if (request()->boolean('embed'))
                            <input type="hidden" name="embed" value="1">
                        @endif
                        <div class="col-12 col-md-3">
                            <label class="form-label">{{ __('Area') }}</label>
                            <select class="form-select" name="area">
                                <option value="All" @selected($area === 'All')>{{ __('All') }}</option>
                                @foreach ($areaCoverages as $a)
                                    <option value="{{ $a->id }}" @selected((string) $area === (string) $a->id)>{{ $a->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">{{ __('Dari') }}</label>
                            <input type="date" name="from" class="form-control" value="{{ $from }}">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">{{ __('Sampai') }}</label>
                            <input type="date" name="to" class="form-control" value="{{ $to }}">
                        </div>
                        <div class="col-12 col-md-3 d-grid">
                            <button class="btn btn-outline-primary" type="submit">{{ __('Refresh') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <form method="POST" action="{{ route('finance-setors.store') }}">
                @csrf
                @if (request()->boolean('embed'))
                    <input type="hidden" name="embed" value="1">
                @endif

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label">{{ __('Tanggal Setor') }}</label>
                                <input type="datetime-local" name="deposited_at" class="form-control" value="{{ old('deposited_at', now()->format('Y-m-d\\TH:i')) }}" required>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">{{ __('Metode') }}</label>
                                <select name="method" class="form-select" required>
                                    <option value="Cash" @selected(old('method', 'Cash') === 'Cash')>Cash</option>
                                    <option value="Transfer Bank" @selected(old('method') === 'Transfer Bank')>Transfer Bank</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">{{ __('Rekening (opsional)') }}</label>
                                <select name="bank_account_id" class="form-select">
                                    <option value="">{{ __('-') }}</option>
                                    @foreach ($bankAccounts as $ba)
                                        <option value="{{ $ba->id }}" @selected((string) old('bank_account_id') === (string) $ba->id)>{{ $ba->nama_bank }} - {{ $ba->nomor_rekening }} ({{ $ba->pemilik_rekening }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2 d-grid">
                                <button class="btn btn-primary" type="submit" onclick="return confirm('Buat setor dari tagihan yang dipilih?')">{{ __('Buat Setor') }}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold">{{ __('Tagihan Menunggu Setor') }}</div>
                            <div class="text-muted">{{ __('Max 500 baris per load') }}</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th style="width:36px;">
                                            <input type="checkbox" id="check-all">
                                        </th>
                                        <th>{{ __('Area') }}</th>
                                        <th>{{ __('No Layanan') }}</th>
                                        <th>{{ __('Pelanggan') }}</th>
                                        <th>{{ __('No Tagihan') }}</th>
                                        <th>{{ __('Periode') }}</th>
                                        <th>{{ __('Tanggal Bayar') }}</th>
                                        <th class="text-end">{{ __('Nominal') }}</th>
                                        <th>{{ __('Kolektor') }}</th>
                                        <th>{{ __('Validator') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($items as $it)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="row-check" name="tagihan_ids[]" value="{{ $it->id }}" checked>
                                            </td>
                                            <td>{{ $it->area_nama ?? '-' }}</td>
                                            <td>{{ isset($it->no_layanan) ? formatNoLayananTenant($it->no_layanan, (int) (auth()->user()->tenant_id ?? 0)) : '-' }}</td>
                                            <td>{{ $it->pelanggan_nama ?? '-' }}</td>
                                            <td>{{ $it->no_tagihan ?? '-' }}</td>
                                            <td>{{ $it->periode ?? '-' }}</td>
                                            <td>{{ $it->tanggal_bayar ?? '-' }}</td>
                                            <td class="text-end">{{ rupiah((int) ($it->total_bayar ?? 0)) }}</td>
                                            <td>{{ $it->collector_name ?? '-' }}</td>
                                            <td>{{ $it->validator_name ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center">{{ __('Tidak ada data') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('js')
    <script>
        (function () {
            const all = document.getElementById('check-all');
            if (!all) return;
            all.addEventListener('change', function () {
                const checks = Array.from(document.querySelectorAll('.row-check'));
                for (const c of checks) c.checked = all.checked;
            });
        })();
    </script>
@endpush
