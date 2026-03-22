@extends('layouts.app')

@section('title', __('Log Mikrotik Automation'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Log Mikrotik Automation') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Riwayat aksi isolir/buka isolir dari menu dan scheduler.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('mikrotik-automation.index') }}">{{ __('Mikrotik Automation') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Log') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <div class="card">
                <div class="card-body">
                    <div class="mb-2">
                        <a class="btn btn-outline-secondary" href="{{ route('mikrotik-automation.index') }}">{{ __('Kembali') }}</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>{{ __('Waktu') }}</th>
                                    <th>{{ __('Aksi') }}</th>
                                    <th>{{ __('Pelanggan') }}</th>
                                    <th>{{ __('Mode') }}</th>
                                    <th>{{ __('Identity') }}</th>
                                    <th>{{ __('Reason') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('By') }}</th>
                                    <th>{{ __('Error') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $l)
                                    <tr>
                                        <td>{{ $l->id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($l->created_at)->format('d-m-Y H:i') }}</td>
                                        <td>{{ $l->action }}</td>
                                        <td>{{ $l->pelanggan_id ?? '-' }}</td>
                                        <td>{{ $l->mode_user ?? '-' }}</td>
                                        <td>{{ $l->identity ?? '-' }}</td>
                                        <td>{{ $l->reason ?? '-' }}</td>
                                        <td>{{ $l->status }}</td>
                                        <td>{{ $l->performed_by_name ?? $l->performed_by ?? '-' }}</td>
                                        <td>{{ $l->error_message ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">{{ __('Belum ada log.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted">{{ __('Maksimal 300 log terbaru.') }}</div>
                </div>
            </div>
        </section>
    </div>
@endsection

