@extends('layouts.app')

@section('title', __('Historical Balance'))


@section('content')
    <div class="page-body">
        {{-- KONTEN HTML ANDA (TIDAK ADA PERUBAHAN) --}}
        <div class="container-fluid">
            <div class="page-header" style="margin-bottom: 5px;">
                <div class="row">
                    <div class="col-sm-6">
                        <h3>{{ __('Historical Balance') }}</h3>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('Historical Balance') }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="filter-form">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label for="start_date">Tanggal Mulai</label>
                                        <input type="date" id="start_date" name="start_date" class="form-control"
                                            value="{{ $start }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="end_date">Tanggal Selesai</label>
                                        <input type="date" id="end_date" name="end_date" class="form-control"
                                            value="{{ $end }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="pelanggan_id">Pelanggan</label>
                                        <select id="pelanggan_id" name="pelanggan_id" class="form-control select2">
                                            <option value="">Semua Pelanggan</option>
                                            @foreach ($pelanggans as $pelanggan)
                                                <option value="{{ $pelanggan->id }}">{{ $pelanggan->nama }}
                                                    ({{ formatNoLayananTenant($pelanggan->no_layanan, (int) (auth()->user()->tenant_id ?? 0)) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="type">Tipe</label>
                                        <select id="type" name="type" class="form-control">
                                            <option value="">Semua Tipe</option>
                                            <option value="Penambahan">Penambahan</option>
                                            <option value="Pengurangan">Pengurangan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" id="filter-btn" class="btn btn-primary">Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="data-table" width="100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>{{ __('Pelanggan') }}</th>
                                            <th>{{ __('Tipe') }}</th>
                                            <th>{{ __('Jumlah') }}</th>
                                            <th>{{ __('Saldo Sebelumnya') }}</th>
                                            <th>{{ __('Saldo Sesudah') }}</th>
                                            <th>{{ __('Deskripsi') }}</th>
                                            <th>{{ __('Tanggal') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.12.0/datatables.min.css" />
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/combine/npm/datatables.net@1.12.0,npm/datatables.net-bs5@1.12.0"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2();

            var dataTable = $('#data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('balance-histories.index') }}',
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.pelanggan_id = $('#pelanggan_id').val();
                        d.type = $('#type').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'pelanggan_nama',
                        name: 'pelanggan_nama'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        className: 'text-end'
                    },
                    {
                        data: 'balance_before',
                        name: 'balance_before',
                        className: 'text-end'
                    },
                    {
                        data: 'balance_after',
                        name: 'balance_after',
                        className: 'text-end'
                    },
                    {
                        data: 'description',
                        name: 'description'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    }
                ]
            });

            $('#filter-btn').on('click', function(e) {
                e.preventDefault();
                dataTable.draw();
            });
        });
    </script>
@endpush
