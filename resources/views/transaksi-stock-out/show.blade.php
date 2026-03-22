@extends('layouts.app')
@section('title', 'Detail Transaksi Stok Keluar')
@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>Detail Transaksi Stok Keluar</h3>
                    <p class="text-subtitle text-muted">Informasi lengkap mengenai transaksi.</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/">@lang('Dashboard')</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('transaksi-stock-out.index') }}">Transaksi Stok Keluar</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Detail</li>
                </x-breadcrumb>
            </div>
        </div>
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Informasi Transaksi</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Kode Transaksi:</strong> {{ $transaksi->kode_transaksi }}</p>
                            <p><strong>Tanggal:</strong>
                                {{ \Carbon\Carbon::parse($transaksi->tanggal_transaksi)->format('d F Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>User:</strong> {{ $transaksi->user->name }}</p>
                            <p><strong>Keterangan:</strong> {{ $transaksi->keterangan ?? '-' }}</p>
                        </div>
                    </div>
                    <hr>
                    <h5 class="card-title mt-4">Detail Barang</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Pemilik</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transaksi->details as $detail)
                                    <tr>
                                        <td>{{ $detail->barang?->nama_barang ?? 'Barang tidak ditemukan' }}</td>
                                        <td>
                                            @if (($detail->owner_type ?? 'office') === 'investor')
                                                Investor: {{ $detail->ownerUser->name ?? '-' }}
                                            @else
                                                Kantor
                                            @endif
                                        </td>
                                        <td>{{ $detail->jumlah }} {{ $detail->barang?->unit_satuan?->nama_satuan ?? '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ route('transaksi-stock-out.index') }}" class="btn btn-secondary mt-3">Kembali</a>
                </div>
            </div>
        </section>
    </div>
@endsection
