@extends('layouts.app')
@section('title', 'Buat Transaksi Stok Masuk')
@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>Transaksi Stok Masuk</h3>
                    <p class="text-subtitle text-muted">Formulir untuk menambah stok barang.</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/">@lang('Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('transaksi-stock-in.index') }}">Transaksi Stok Masuk</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Buat</li>
                </x-breadcrumb>
            </div>
        </div>
        <section class="section">
            <x-alert></x-alert>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="transactionForm" method="POST" action="{{ route('transaksi-stock-in.store') }}">
                                @csrf
                                @include('transaksi-stock-in.include.form', ['type' => 'in'])
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
