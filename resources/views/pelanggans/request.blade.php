@extends('layouts.app')

@section('title', __('Request Pelanggan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Request Pelanggan') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Berikut adalah daftar pelanggan yang mengisi form pendaftaran.') }}
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pelanggans.index') }}">{{ __('Pelanggan') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Request') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive p-1">
                                <table id="data-table" class="table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>{{ __('Tanggal Request') }}</th>
                                            <th>{{ __('Nama') }}</th>
                                            <th>{{ __('Email') }}</th>
                                            <th>{{ __('No WhatsApp') }}</th>
                                            <th>{{ __('NIK / No KTP') }}</th>
                                            <th>{{ __('Alamat') }}</th>
                                            <th>{{ __('Kode Referal') }}</th>
                                            <th>{{ __('Koordinat') }}</th>
                                            <th>{{ __('Foto KTP') }}</th>
                                            <th>{{ __('Status Gudang') }}</th>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.12.0/datatables.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/combine/npm/datatables.net@1.12.0,npm/datatables.net-bs5@1.12.0"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        let columns = [{
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'tanggal_request',
                name: 'created_at'
            },
            {
                data: 'nama',
                name: 'nama',
            },
            {
                data: 'email',
                name: 'email',
            },
            {
                data: 'no_wa',
                name: 'no_wa',
            },
            {
                data: 'no_ktp',
                name: 'no_ktp',
            },
            {
                data: 'alamat',
                name: 'alamat',
                render: function(data) {
                    return data ? `${data.substring(0, 80)}${data.length > 80 ? '...' : ''}` : '-';
                }
            },
            {
                data: 'kode_referal',
                name: 'kode_referal',
                render: function(data) {
                    return data || '-';
                }
            },
            {
                data: null,
                name: 'latitude',
                render: function(data, type, row) {
                    const lat = row.latitude || '-';
                    const lng = row.longitude || '-';
                    if (lat === '-' || lng === '-') return '-';
                    return `${lat}, ${lng}`;
                }
            },
            {
                data: 'photo_ktp',
                name: 'photo_ktp',
                orderable: false,
                searchable: false,
                render: function(data) {
                    if (!data) return '-';
                    const src = `/storage/uploads/photo_ktps/${data}`;
                    return `<img src="${src}" alt="KTP" style="width:72px;height:72px;object-fit:cover;border-radius:6px;">`;
                }
            },
            {
                data: 'material_status',
                name: 'material_status',
                render: function(data) {
                    if (data === 'Approved') {
                        return `<span class="badge bg-success">Approved</span>`;
                    }
                    return `<span class="badge bg-warning text-dark">Pending</span>`;
                }
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let btn = `<div class="btn-group">`;
                    // Tombol Approve / Reject bawaan (biasanya di-render di server side, tapi kita tambahkan manual tombol cetak)
                    // Karena 'action' dari server biasanya sudah berisi HTML tombol, kita append saja.
                    // Namun jika kita ingin kontrol penuh, lebih baik custom di controller. 
                    // Untuk amannya, kita tambahkan tombol cetak ini secara terpisah atau append ke string action yang ada.
                    
                    // Cara paling aman tanpa merusak tombol action bawaan adalah membuat kolom baru atau memodifikasi controller.
                    // Tapi karena di sini kita pakai render function, kita bisa memanipulasi string HTML-nya.
                    
                    let printBtn = `<a href="/cetakSurat/${row.id}" target="_blank" class="btn btn-sm btn-info text-white" title="Cetak Surat"><i class="fas fa-print"></i></a> `;
                    
                    // Jika data action dari server adalah string HTML
                    if (data && typeof data === 'string') {
                        return printBtn + data;
                    }
                    return printBtn;
                }
            }
        ];

        var table = $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('pelanggans-request.data') }}"
            },
            columns: columns
        });
    </script>
@endpush
