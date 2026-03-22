@extends('layouts.app')
@section('title', 'Edit Transaksi Stok Keluar')
@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>Edit Transaksi Stok Keluar</h3>
                    <p class="text-subtitle text-muted">Formulir untuk mengubah data stok keluar.</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/">@lang('Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('transaksi-stock-out.index') }}">Transaksi Stok Keluar</a>
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
                                action="{{ route('transaksi-stock-out.update', $transaksi->id) }}">
                                @csrf
                                @method('PUT')
                                {{-- Menggunakan form yang sama dari stok masuk --}}
                                @include('transaksi-stock-in.include.form', ['type' => 'out'])
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
            'purpose' => $detail->purpose ?? 'umum',
            'target_pelanggan_id' => $detail->target_pelanggan_id ?? null,
        ];
    });
@endphp

@push('js')
    <script>
        // Definisikan data keranjang yang sudah ada sebagai variabel global
        window.existingCartItems = @json($cartItemsForJs);
    </script>
@endpush
