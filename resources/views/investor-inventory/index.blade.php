@extends('layouts.app')

@section('title', __('Inventory Investor/Mitra'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Inventory Investor/Mitra') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Stok barang kepemilikan Anda.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Inventory Investor/Mitra') }}</li>
                </x-breadcrumb>
            </div>
        </div>
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <div><strong>{{ __('Stok Gudang') }}</strong>: {{ (int) ($summary['stock_total_qty'] ?? 0) }} | Rp {{ number_format((int) ($summary['stock_total_nilai'] ?? 0), 0, ',', '.') }}</div>
                        <div><strong>{{ __('Terpasang di Pelanggan') }}</strong>: {{ (int) ($summary['deployed_total_qty'] ?? 0) }} | Rp {{ number_format((int) ($summary['deployed_total_nilai'] ?? 0), 0, ',', '.') }}</div>
                        <div><strong>{{ __('Total Nilai Barang') }}</strong>: {{ (int) ($summary['total_qty'] ?? 0) }} | Rp {{ number_format((int) ($summary['total_nilai'] ?? 0), 0, ',', '.') }}</div>
                    </div>
                    <div class="mb-4">
                        <h4 class="mb-2">{{ __('Barang Terpasang') }}</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Barang</th>
                                        <th>Kategori</th>
                                        <th>Satuan</th>
                                        <th>Qty</th>
                                        <th>Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($deployed as $s)
                                        <tr>
                                            <td>{{ $s->kode_barang }}</td>
                                            <td>{{ $s->nama_barang }}</td>
                                            <td>{{ $s->nama_kategori_barang ?? '-' }}</td>
                                            <td>{{ $s->nama_unit_satuan ?? '-' }}</td>
                                            <td>{{ (int) $s->qty }}</td>
                                            <td>Rp {{ number_format((int) ($s->total_nilai ?? 0), 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">{{ __('Belum ada barang terpasang.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <h4 class="mb-2">{{ __('Stok Gudang') }}</h4>
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Satuan</th>
                                    <th>Stok</th>
                                    <th>Harga</th>
                                    <th>Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stocks as $s)
                                    <tr>
                                        <td>{{ $s->kode_barang }}</td>
                                        <td>{{ $s->nama_barang }}</td>
                                        <td>{{ $s->nama_kategori_barang ?? '-' }}</td>
                                        <td>{{ $s->nama_unit_satuan ?? '-' }}</td>
                                        <td>{{ (int) $s->qty }}</td>
                                        <td>Rp {{ number_format((int) ($s->harga_jual_unit ?? 0), 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format((int) ($s->total_nilai ?? 0), 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('Belum ada stok kepemilikan investor/mitra.') }}</td>
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
