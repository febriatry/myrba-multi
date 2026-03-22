@extends('layouts.app')

@section('title', __('Detail Kasbon'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Detail Kasbon') }}</h3>
                    <p class="text-subtitle text-muted">{{ $row->user_name }} | {{ $row->date }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-kasbons.index') }}">{{ __('Kasbon') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Detail') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="fw-bold">{{ __('Karyawan') }}</div>
                            <div>{{ $row->user_name }} ({{ $row->user_email }})</div>
                        </div>
                        <div class="col-md-2">
                            <div class="fw-bold">{{ __('Nominal') }}</div>
                            <div>{{ $row->amount }}</div>
                        </div>
                        <div class="col-md-2">
                            <div class="fw-bold">{{ __('Sisa') }}</div>
                            <div>{{ $row->remaining_amount }}</div>
                        </div>
                        <div class="col-md-2">
                            <div class="fw-bold">{{ __('Status') }}</div>
                            <div>{{ $row->status }}</div>
                        </div>
                        <div class="col-md-2">
                            <div class="fw-bold">{{ __('Keuangan') }}</div>
                            <div>{{ $row->finance_pengeluaran_id ? ('pengeluaran #' . $row->finance_pengeluaran_id) : '-' }}</div>
                        </div>
                    </div>
                    @if (!empty($row->note))
                        <div class="mt-2"><span class="fw-bold">{{ __('Catatan') }}:</span> {{ $row->note }}</div>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <div class="fw-bold">{{ __('Tambah Pembayaran Kasbon') }}</div>
                </div>
                <div class="card-body">
                    <form class="row g-2" method="POST" action="{{ route('hr-kasbons.repayments.store', $row->id) }}">
                        @csrf
                        <div class="col-md-2">
                            <input type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="amount" class="form-control" value="{{ old('amount', 0) }}" required>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="source" required>
                                <option value="payroll" @selected(old('source', 'payroll') === 'payroll')>payroll</option>
                                <option value="cash" @selected(old('source') === 'cash')>cash</option>
                                <option value="transfer" @selected(old('source') === 'transfer')>transfer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="note" class="form-control" value="{{ old('note') }}" placeholder="Keterangan">
                        </div>
                        <div class="col-12 d-grid">
                            <button class="btn btn-primary" type="submit">{{ __('Tambah') }}</button>
                        </div>
                    </form>
                    <div class="text-muted mt-2">
                        {{ __('Jika source = payroll: akan memotong payroll periode sesuai tanggal, tanpa pemasukan keuangan. Jika cash/transfer: otomatis masuk pemasukan keuangan.') }}
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="fw-bold">{{ __('Riwayat Pembayaran') }}</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Tanggal') }}</th>
                                    <th>{{ __('Nominal') }}</th>
                                    <th>{{ __('Source') }}</th>
                                    <th>{{ __('Note') }}</th>
                                    <th>{{ __('Keuangan') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($repayments as $r)
                                    <tr>
                                        <td>{{ $r->id }}</td>
                                        <td>{{ $r->date }}</td>
                                        <td>{{ $r->amount }}</td>
                                        <td>{{ $r->source }}</td>
                                        <td>{{ $r->note ?? '-' }}</td>
                                        <td>{{ $r->finance_pemasukan_id ? ('pemasukan #' . $r->finance_pemasukan_id) : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('Belum ada pembayaran') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $repayments->links() }}
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <a href="{{ route('hr-kasbons.index') }}" class="btn btn-light">{{ __('Kembali') }}</a>
            </div>
        </section>
    </div>
@endsection

