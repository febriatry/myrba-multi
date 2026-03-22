@extends('layouts.app')

@section('title', __('Pemasukan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Pemasukan') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Transaksi pemasukan dan kategori pemasukan.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Pemasukan') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <ul class="nav nav-tabs mb-3">
                @can('pemasukan view')
                    <li class="nav-item">
                        <a class="nav-link @if (($tab ?? 'transaksi') === 'transaksi') active @endif" href="{{ route('finance-income.index', ['tab' => 'transaksi']) }}">{{ __('Transaksi') }}</a>
                    </li>
                @endcan
                @can('category pemasukan view')
                    <li class="nav-item">
                        <a class="nav-link @if (($tab ?? '') === 'kategori') active @endif" href="{{ route('finance-income.index', ['tab' => 'kategori']) }}">{{ __('Kategori') }}</a>
                    </li>
                @endcan
            </ul>

            <div class="card">
                <div class="card-body">
                    @if (($tab ?? 'transaksi') === 'kategori')
                        @can('category pemasukan view')
                            <iframe src="{{ route('category-pemasukans.index', ['embed' => 1]) }}" style="width:100%; height: 80vh; border:0;"></iframe>
                        @else
                            <div class="text-muted">{{ __('Tidak memiliki akses kategori pemasukan.') }}</div>
                        @endcan
                    @else
                        @can('pemasukan view')
                            <iframe src="{{ route('pemasukans.index', ['embed' => 1]) }}" style="width:100%; height: 80vh; border:0;"></iframe>
                        @else
                            <div class="text-muted">{{ __('Tidak memiliki akses pemasukan.') }}</div>
                        @endcan
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection
