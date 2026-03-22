@extends('layouts.app')

@section('title', __('Master Barang'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Master Barang') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Barang, kategori barang, dan unit satuan.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Master Barang') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <ul class="nav nav-tabs mb-3">
                @can('barang view')
                    <li class="nav-item">
                        <a class="nav-link @if (($tab ?? 'barang') === 'barang') active @endif" href="{{ route('inventory-master.index', ['tab' => 'barang']) }}">{{ __('Barang') }}</a>
                    </li>
                @endcan
                @can('kategori barang view')
                    <li class="nav-item">
                        <a class="nav-link @if (($tab ?? '') === 'kategori') active @endif" href="{{ route('inventory-master.index', ['tab' => 'kategori']) }}">{{ __('Kategori Barang') }}</a>
                    </li>
                @endcan
                @can('unit satuan view')
                    <li class="nav-item">
                        <a class="nav-link @if (($tab ?? '') === 'unit') active @endif" href="{{ route('inventory-master.index', ['tab' => 'unit']) }}">{{ __('Unit Satuan') }}</a>
                    </li>
                @endcan
            </ul>

            <div class="card">
                <div class="card-body">
                    @if (($tab ?? 'barang') === 'kategori')
                        @can('kategori barang view')
                            <iframe src="{{ route('kategori-barangs.index', ['embed' => 1]) }}" style="width:100%; height: 80vh; border:0;"></iframe>
                        @else
                            <div class="text-muted">{{ __('Tidak memiliki akses kategori barang.') }}</div>
                        @endcan
                    @elseif (($tab ?? 'barang') === 'unit')
                        @can('unit satuan view')
                            <iframe src="{{ route('unit-satuans.index', ['embed' => 1]) }}" style="width:100%; height: 80vh; border:0;"></iframe>
                        @else
                            <div class="text-muted">{{ __('Tidak memiliki akses unit satuan.') }}</div>
                        @endcan
                    @else
                        @can('barang view')
                            <iframe src="{{ route('barangs.index', ['embed' => 1]) }}" style="width:100%; height: 80vh; border:0;"></iframe>
                        @else
                            <div class="text-muted">{{ __('Tidak memiliki akses barang.') }}</div>
                        @endcan
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection

