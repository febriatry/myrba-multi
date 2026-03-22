@extends('layouts.app')

@section('title', __('Detail of Barang'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Barang') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Detail of barang.') }}
                    </p>
                </div>

                <x-breadcrumb>
                    <li class="breadcrumb-item">
                        <a href="/dashboard">{{ __('Dashboard') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('barangs.index') }}">{{ __('Barang') }}</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ __('Detail') }}
                    </li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <tr>
                                            <td class="fw-bold">{{ __('Kode Barang') }}</td>
                                            <td>{{ $barang->kode_barang }}</td>
                                        </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Nama Barang') }}</td>
                                            <td>{{ $barang->nama_barang }}</td>
                                        </tr>
									<tr>
                                        <td class="fw-bold">{{ __('Unit Satuan') }}</td>
                                        <td>{{ $barang->unit_satuan ? $barang->unit_satuan->id : '' }}</td>
                                    </tr>
									<tr>
                                        <td class="fw-bold">{{ __('Kategori Barang') }}</td>
                                        <td>{{ $barang->kategori_barang ? $barang->kategori_barang->id : '' }}</td>
                                    </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Deskripsi Barang') }}</td>
                                            <td>{{ $barang->deskripsi_barang }}</td>
                                        </tr>
									<tr>
                                        <td class="fw-bold">{{ __('Photo Barang') }}</td>
                                        <td>
                                            @if ($barang->photo_barang == null)
                                            <img src="https://via.placeholder.com/350?text=No+Image+Avaiable" alt="Photo Barang"  class="rounded" width="200" height="150" style="object-fit: cover">
                                            @else
                                                <img src="{{ asset('storage/uploads/photo_barangs/' . $barang->photo_barang) }}" alt="Photo Barang" class="rounded" width="200" height="150" style="object-fit: cover">
                                            @endif
                                        </td>
                                    </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Stock') }}</td>
                                            <td>{{ $barang->stock }}</td>
                                        </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Created at') }}</td>
                                        <td>{{ $barang->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Updated at') }}</td>
                                        <td>{{ $barang->updated_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>

                            <a href="{{ url()->previous() }}" class="btn btn-secondary">{{ __('Back') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
