@extends('layouts.app')

@section('title', 'WA Broadcast Tunggakan')

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>WA Broadcast Tunggakan</h3>
                    <p class="text-subtitle text-muted">Kirim pesan WA berdasarkan ringkasan tunggakan dari Audit Keuangan.</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">WA Broadcast Tunggakan</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="POST" action="{{ route('wa-tunggakan.send') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-12 col-md-4">
                            <label class="form-label">Area Coverage (opsional)</label>
                            <select name="area_id" id="filter-area" class="form-select">
                                <option value="">Semua Area</option>
                                @foreach ($areaCoverages as $a)
                                    <option value="{{ $a->id }}">{{ $a->kode_area }} - {{ $a->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">Min Bulan</label>
                            <input type="number" name="min_months" id="filter-min-months" class="form-control" min="1" value="1">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Target</label>
                            <select name="only_sendable" id="filter-only-sendable" class="form-select">
                                <option value="1" selected>Hanya yang bisa dikirim</option>
                                <option value="0">Semua (termasuk tanpa WA)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Limit</label>
                            <input type="number" name="limit" class="form-control" min="1" max="5000" value="500">
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="dry_run" value="1" id="dry-run" checked>
                                <label class="form-check-label" for="dry-run">Dry run (tidak benar-benar kirim WA)</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 text-end">
                            <button type="button" id="btn-preview" class="btn btn-outline-primary">Preview</button>
                            <button class="btn btn-primary">Kirim</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="tunggakan-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th>Area</th>
                                    <th>ID Layanan</th>
                                    <th>Nama</th>
                                    <th>No WA</th>
                                    <th>Bulan</th>
                                    <th>Total</th>
                                    <th>Periode Tertunggak</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.12.0/datatables.min.css" />
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/combine/npm/datatables.net@1.12.0,npm/datatables.net-bs5@1.12.0"></script>
    <script>
        const table = $('#tunggakan-table').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ajax: {
                url: '{{ route('wa-tunggakan.data') }}',
                data: function(d) {
                    d.area_id = $('#filter-area').val();
                    d.min_months = $('#filter-min-months').val();
                    d.only_sendable = $('#filter-only-sendable').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'kode_area',
                    name: 'kode_area',
                    render: function(data, type, row) {
                        const area = (row.area_nama || '');
                        return (data || '-') + (area ? (' - ' + area) : '');
                    }
                },
                {
                    data: 'no_layanan',
                    name: 'no_layanan'
                },
                {
                    data: 'nama',
                    name: 'nama'
                },
                {
                    data: 'no_wa',
                    name: 'no_wa',
                    render: function(data) {
                        return data || '-';
                    }
                },
                {
                    data: 'unpaid_count',
                    name: 'unpaid_count'
                },
                {
                    data: 'total_tunggakan',
                    name: 'total_tunggakan'
                },
                {
                    data: 'periode_list',
                    name: 'periode_list',
                    render: function(data) {
                        return data || '-';
                    }
                },
            ]
        });

        $('#btn-preview').on('click', function() {
            table.ajax.reload();
        });
    </script>
@endpush

