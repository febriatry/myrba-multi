@extends('layouts.app')
@section('title', 'Edit Transaksi Stok Masuk')
@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>Edit Transaksi Stok Masuk</h3>
                    <p class="text-subtitle text-muted">Formulir untuk mengubah data stok masuk.</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/">@lang('Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('transaksi-stock-in.index') }}">Transaksi Stok Masuk</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </x-breadcrumb>
            </div>
        </div>
        <section class="section">
            <x-alert></x-alert>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="transactionForm" method="POST"
                                action="{{ route('transaksi-stock-in.update', $transaksi->id) }}">
                                @csrf
                                @method('PUT')
                                {{-- Menggunakan form yang sama, data cart akan di-load via JS --}}
                                @include('transaksi-stock-in.include.form', ['type' => 'in'])
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@php
    // Siapkan data detail transaksi menjadi array PHP yang bersih
    $cartItemsForJs = $transaksi->details->map(function ($detail) {
        return [
            'id' => $detail->barang_id,
            'nama_barang' => $detail->barang->nama_barang,
            'qty' => $detail->jumlah,
            'owner_type' => $detail->owner_type ?? 'office',
            'owner_user_id' => $detail->owner_user_id,
            'owner_name' => $detail->ownerUser->name ?? null,
            'owner_label' => ($detail->owner_type ?? 'office') === 'investor' ? 'Investor: ' . ($detail->ownerUser->name ?? '-') : 'Kantor',
            'hpp_unit' => (int) ($detail->hpp_unit ?? 0),
            'harga_jual_unit' => (int) ($detail->harga_jual_unit ?? 0),
        ];
    });
@endphp

@push('js')
    <script>
        window.existingCartItems = @json($cartItemsForJs);
    </script>
@endpush
