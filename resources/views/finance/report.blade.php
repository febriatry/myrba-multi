@extends('layouts.app')

@section('title', __('Laporan Keuangan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Laporan Keuangan') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Laporan dan audit keuangan.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Laporan Keuangan') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <ul class="nav nav-tabs mb-3">
                @can('laporan view')
                    <li class="nav-item">
                        <a class="nav-link @if (($tab ?? 'laporan') === 'laporan') active @endif" href="{{ route('finance-report.index', ['tab' => 'laporan']) }}">{{ __('Laporan') }}</a>
                    </li>
                @endcan
                @can('audit keuangan view')
                    <li class="nav-item">
                        <a class="nav-link @if (($tab ?? '') === 'audit') active @endif" href="{{ route('finance-report.index', ['tab' => 'audit']) }}">{{ __('Audit Keuangan') }}</a>
                    </li>
                @endcan
            </ul>

            <div class="card">
                <div class="card-body">
                    @if (($tab ?? 'laporan') === 'audit')
                        @can('audit keuangan view')
                            <iframe src="{{ route('audit-keuangan.index', ['embed' => 1]) }}" style="width:100%; height: 80vh; border:0;"></iframe>
                        @else
                            <div class="text-muted">{{ __('Tidak memiliki akses audit keuangan.') }}</div>
                        @endcan
                    @else
                        @can('laporan view')
                            <iframe src="{{ route('laporans.index', ['embed' => 1]) }}" style="width:100%; height: 80vh; border:0;"></iframe>
                        @else
                            <div class="text-muted">{{ __('Tidak memiliki akses laporan.') }}</div>
                        @endcan
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection
