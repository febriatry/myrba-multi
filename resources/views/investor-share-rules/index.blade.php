@extends('layouts.app')

@section('title', __('Aturan Bagi Hasil'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Aturan Bagi Hasil') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Atur mulai periode perhitungan dan besaran bagi hasil.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Aturan Bagi Hasil') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-end mb-3">
                        <a href="{{ route('investor-share-rules.create') }}" class="btn btn-primary">{{ __('Tambah Rule') }}</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Investor') }}</th>
                                    <th>{{ __('Rule') }}</th>
                                    <th>{{ __('Area') }}</th>
                                    <th>{{ __('Paket') }}</th>
                                    <th>{{ __('Mulai Periode') }}</th>
                                    <th>{{ __('Nilai') }}</th>
                                    <th>{{ __('Checklist') }}</th>
                                    <th>{{ __('Aktif') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rules as $r)
                                    <tr>
                                        <td>{{ $r->user_name ?? '-' }}</td>
                                        <td>{{ $r->rule_type }}</td>
                                        <td>{{ $r->area_nama ?? '-' }}</td>
                                        <td>{{ $r->paket_nama ?? '-' }}</td>
                                        <td>{{ $r->start_period ?? '-' }}</td>
                                        <td>{{ $r->amount_type }} {{ rtrim(rtrim(number_format($r->amount_value, 2, '.', ''), '0'), '.') }}</td>
                                        <td>{{ (int) ($r->pelanggan_selected_count ?? 0) }}</td>
                                        <td>{{ $r->is_aktif }}</td>
                                        <td class="d-flex gap-2">
                                            <a href="{{ route('investor-share-rules.customers', $r->id) }}" class="btn btn-sm btn-info">Pelanggan</a>
                                            <a href="{{ route('investor-share-rules.backfill', $r->id) }}" class="btn btn-sm btn-secondary">Backfill</a>
                                            <a href="{{ route('investor-share-rules.edit', $r->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('investor-share-rules.destroy', $r->id) }}" method="post">
                                                @csrf
                                                @method('delete')
                                                <button class="btn btn-sm btn-danger" onclick="return confirm('Hapus rule ini?')">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">{{ __('Belum ada rule.') }}</td>
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
