@extends('layouts.app')
@section('title', __('Laporan Pergerakan Barang'))
@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Laporan Pergerakan Barang') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Filter dan generate laporan pergerakan stok (kartu stok) dalam format Excel.') }}
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Laporan Barang') }}</li>
                </x-breadcrumb>
            </div>
        </div>
        <section class="section">
            <x-alert></x-alert>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Filter Laporan</h4>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('laporan-barang.index') }}" method="GET">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tanggal_mulai">{{ __('Tanggal Mulai') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                                                class="form-control @error('tanggal_mulai') is-invalid @enderror"
                                                value="{{ old('tanggal_mulai', request('tanggal_mulai') ?? date('Y-m-01')) }}"
                                                required>
                                            @error('tanggal_mulai')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tanggal_selesai">{{ __('Tanggal Selesai') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="tanggal_selesai" id="tanggal_selesai"
                                                class="form-control @error('tanggal_selesai') is-invalid @enderror"
                                                value="{{ old('tanggal_selesai', request('tanggal_selesai') ?? date('Y-m-d')) }}"
                                                required>
                                            @error('tanggal_selesai')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="barang_id">{{ __('Nama Barang (Opsional)') }}</label>
                                            <select name="barang_id" id="barang_id"
                                                class="form-select select2 @error('barang_id') is-invalid @enderror">
                                                <option value="" selected>-- Semua Barang --</option>
                                                @foreach ($barangs as $barang)
                                                    <option value="{{ $barang->id }}"
                                                        {{ old('barang_id', request('barang_id')) == $barang->id ? 'selected' : '' }}>
                                                        {{ $barang->nama_barang }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('barang_id')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 d-flex justify-content-start">
                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="fas fa-search"></i> {{ __('Tampilkan') }}
                                        </button>
                                        @php
                                            $qs = request()->only(['tanggal_mulai', 'tanggal_selesai', 'barang_id']);
                                        @endphp
                                        <a class="btn btn-success me-2" href="{{ route('laporan-barang.exportExcel', $qs) }}">
                                            <i class="fas fa-file-excel"></i> {{ __('Generate Excel') }}
                                        </a>
                                        <a href="{{ route('laporan-barang.index') }}" class="btn btn-secondary">
                                            <i class="bi bi-arrow-repeat"></i> Reset Filter
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if (!empty($laporan))
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('Preview Laporan') }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th style="width:60px">No</th>
                                                <th style="width:120px">Tanggal</th>
                                                <th style="width:160px">Kode Transaksi</th>
                                                <th>Keterangan</th>
                                                <th style="width:120px">HPP/Unit</th>
                                                <th style="width:120px">Harga/Unit</th>
                                                <th style="width:90px">Masuk</th>
                                                <th style="width:90px">Keluar</th>
                                                <th style="width:110px">Stock Akhir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($laporan as $item)
                                                @if ($item->is_header)
                                                    <tr>
                                                        <td colspan="9">
                                                            <strong>{{ __('Nama Barang') }}:</strong> {{ $item->nama_barang_header }}
                                                            | <strong>{{ __('Pemilik') }}:</strong> {{ $item->owner_label ?? 'Kantor' }}
                                                            | <strong>{{ __('Stock Awal') }}:</strong> {{ (int) $item->saldo_awal }}
                                                        </td>
                                                    </tr>
                                                @else
                                                    <tr>
                                                        <td class="text-center">{{ (int) ($item->no ?? 0) }}</td>
                                                        <td>{{ \Carbon\Carbon::parse($item->tanggal_transaksi)->format('d-m-Y') }}</td>
                                                        <td>{{ $item->kode_transaksi }}</td>
                                                        <td>{{ $item->keterangan }}</td>
                                                        <td class="text-end">{{ (int) ($item->hpp_unit ?? 0) }}</td>
                                                        <td class="text-end">{{ (int) ($item->harga_jual_unit ?? 0) }}</td>
                                                        <td class="text-end">{{ (int) ($item->masuk ?? 0) ?: '' }}</td>
                                                        <td class="text-end">{{ (int) ($item->keluar ?? 0) ?: '' }}</td>
                                                        <td class="text-end">{{ (int) ($item->saldo_akhir ?? 0) }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "-- Pilih --",
                width: '100%'
            });
        });
    </script>
@endpush
