@extends('layouts.app')

@section('title', __('Setor'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Setor') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Posting pemasukan dari tagihan cash/transfer dilakukan setelah setor di-approve.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Setor') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('setor create')
                    <a class="btn btn-primary" href="{{ route('finance-setors.create', request()->boolean('embed') ? ['embed' => 1] : []) }}">{{ __('Buat Setor') }}</a>
                @endcan
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('finance-setors.index') }}" class="row g-2 align-items-end">
                        @if (request()->boolean('embed'))
                            <input type="hidden" name="embed" value="1">
                        @endif
                        <div class="col-12 col-md-3">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" class="form-select">
                                <option value="pending" @selected($status === 'pending')>{{ __('Pending') }}</option>
                                <option value="approved" @selected($status === 'approved')>{{ __('Approved') }}</option>
                                <option value="rejected" @selected($status === 'rejected')>{{ __('Rejected') }}</option>
                                <option value="all" @selected($status === 'all')>{{ __('Semua') }}</option>
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
                            <button class="btn btn-outline-primary" type="submit">{{ __('Filter') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Kode') }}</th>
                                    <th>{{ __('Tanggal Setor') }}</th>
                                    <th>{{ __('Penyetor') }}</th>
                                    <th>{{ __('Metode') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                    <th>{{ __('Items') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $r)
                                    <tr>
                                        <td>{{ $r->id }}</td>
                                        <td class="fw-bold">{{ $r->code }}</td>
                                        <td>{{ $r->deposited_at }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $r->depositor_name ?? '-' }}</div>
                                            <div class="text-muted">{{ $r->depositor_email ?? '' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $r->method }}</div>
                                            @if (!empty($r->bank_name))
                                                <div class="text-muted">{{ $r->bank_name }} {{ $r->bank_number }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ rupiah((int) ($r->total_nominal ?? 0)) }}</td>
                                        <td>{{ (int) ($r->total_items ?? 0) }}</td>
                                        <td>{{ $r->status }}</td>
                                        <td class="d-flex gap-2 flex-wrap align-items-center">
                                            @can('setor export pdf')
                                                <a class="btn btn-sm btn-outline-danger" target="_blank" href="{{ route('finance-setors.exportPdf', $r->id) }}">{{ __('PDF') }}</a>
                                            @endcan
                                            @can('setor approve')
                                                @if (($r->status ?? '') === 'pending')
                                                    <form method="POST" action="{{ route('finance-setors.approve', $r->id) }}">
                                                        @csrf
                                                        <button class="btn btn-sm btn-success" onclick="return confirm('Approve setor ini dan posting ke pemasukan?')">{{ __('Approve') }}</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('finance-setors.reject', $r->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="note" value="">
                                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Tolak setor ini?')">{{ __('Reject') }}</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">{{ __('Tidak ada data') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $rows->links() }}
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

