@extends('layouts.app')

@section('title', __('Transaksi Barang'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Transaksi Barang') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Stock in dan stock out.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Transaksi Barang') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <ul class="nav nav-tabs mb-3">
                @can('transaksi stock in view')
                    <li class="nav-item">
                        <a class="nav-link @if (($tab ?? 'in') === 'in') active @endif" href="{{ route('inventory-transactions.index', ['tab' => 'in']) }}">{{ __('Stock In') }}</a>
                    </li>
                @endcan
                @can('transaksi stock out view')
                    <li class="nav-item">
                        <a class="nav-link @if (($tab ?? '') === 'out') active @endif" href="{{ route('inventory-transactions.index', ['tab' => 'out']) }}">{{ __('Stock Out') }}</a>
                    </li>
                @endcan
            </ul>

            <div class="card">
                <div class="card-body">
                    @if (($tab ?? 'in') === 'out')
                        @can('transaksi stock out view')
                            <iframe src="{{ route('transaksi-stock-out.index', ['embed' => 1]) }}" style="width:100%; height: 80vh; border:0;"></iframe>
                        @else
                            <div class="text-muted">{{ __('Tidak memiliki akses stock out.') }}</div>
                        @endcan
                    @else
                        @can('transaksi stock in view')
                            <iframe src="{{ route('transaksi-stock-in.index', ['embed' => 1]) }}" style="width:100%; height: 80vh; border:0;"></iframe>
                        @else
                            <div class="text-muted">{{ __('Tidak memiliki akses stock in.') }}</div>
                        @endcan
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection

