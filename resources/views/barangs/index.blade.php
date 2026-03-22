@extends('layouts.app')

@section('title', __('Barang'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Barang') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Berikut adalah daftar semua Barang.') }}
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Barang') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

                @can('barang create')
                <div class="d-flex justify-content-end">
                    <a href="{{ route('barangs.create') }}" class="btn btn-primary mb-3">
                        <i class="fas fa-plus"></i>
                        {{ __('Tambah barang') }}
                    </a>
                </div>
                @endcan

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive p-1">
                                <table class="table table-striped" id="data-table" width="100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>{{ __('Kode Barang') }}</th>
											<th>{{ __('Nama Barang') }}</th>
											<th>{{ __('Unit Satuan') }}</th>
											<th>{{ __('Kategori Barang') }}</th>
											<th>{{ __('Deskripsi Barang') }}</th>
											<th>{{ __('Photo Barang') }}</th>
											<th>{{ __('Stock Kantor') }}</th>
											<th>{{ __('Stock Investor') }}</th>
											<th>{{ __('Stock Total') }}</th>
                                            <th>{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.12.0/datatables.min.css" />
@endpush

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/combine/npm/datatables.net@1.12.0,npm/datatables.net-bs5@1.12.0"></script>
    <script>
        $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('barangs.index') }}",
            columns: [
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'kode_barang',
                    name: 'kode_barang',
                },
				{
                    data: 'nama_barang',
                    name: 'nama_barang',
                },
				{
                    data: 'unit_satuan',
                    name: 'unit_satuan.id'
                },
				{
                    data: 'kategori_barang',
                    name: 'kategori_barang.id'
                },
				{
                    data: 'deskripsi_barang',
                    name: 'deskripsi_barang',
                },
				{
                    data: 'photo_barang',
                    name: 'photo_barang',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, full, meta) {
                        return `<div>
                            <img src="${data}" alt="Photo Barang" width="80px" >
                        </div>`;
                        }
                    },
				{
                    data: 'stock_office',
                    name: 'stock',
                },
                {
                    data: 'stock_investor',
                    name: 'stock_investor',
                    searchable: false,
                },
                {
                    data: 'stock_total',
                    name: 'stock_total',
                    searchable: false,
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
        });
    </script>
@endpush
