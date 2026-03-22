@extends('layouts.app')

@section('title', __('Withdraw'))

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
@endpush

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-6">
                        <h3>{{ __('Withdraw') }}</h3>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('Withdraw') }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            {{-- REVISI DIMULAI DARI SINI --}}
            @can('withdraw create')
                <div class="d-flex justify-content-end">
                    <a href="{{ route('withdraws.create') }}" class="btn btn-primary mb-3">
                        <i class="fas fa-plus"></i>
                        {{ __('Buat Permintaan') }}
                    </a>
                </div>
            @endcan
            {{-- REVISI SELESAI --}}
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="display table table-bordered" id="data-table" width="100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>{{ __('Pelanggan') }}</th>
                                            <th>{{ __('Nominal') }}</th>
                                            <th>{{ __('Tanggal') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Disetujui Oleh') }}</th>
                                            <th>{{ __('Aksi') }}</th>
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

    <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approvalModalLabel">Persetujuan Withdraw</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="approvalForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p>Anda akan memproses permintaan withdraw untuk:</p>
                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Pelanggan
                                <span id="modal-pelanggan" class="badge bg-primary rounded-pill"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Nominal
                                <span id="modal-nominal" class="badge bg-primary rounded-pill"></span>
                            </li>
                        </ul>

                        <div class="form-group">
                            <label for="catatan">Catatan (Opsional)</label>
                            <textarea name="catatan" id="catatan" class="form-control" rows="3"
                                placeholder="Catatan akan dibuat otomatis jika kosong"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">Tolak</button>
                        <button type="submit" name="action" value="approve" class="btn btn-success">Setujui</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            var dataTable = $('#data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('withdraws.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    }, {
                        data: 'pelanggan',
                        name: 'pelanggans.nama'
                    },
                    {
                        data: 'nominal_wd',
                        name: 'nominal_wd',
                        className: 'text-end'
                    },
                    {
                        data: 'tanggal_wd',
                        name: 'tanggal_wd',
                        render: function(data) {
                            return new Date(data).toLocaleDateString('id-ID', {
                                day: '2-digit',
                                month: 'long',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center'
                    },
                    {
                        data: 'user_approved',
                        name: 'approver.name'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
            });

            $('#data-table').on('click', '.approval-btn', function() {
                var id = $(this).data('id');
                var pelanggan = $(this).data('pelanggan');
                var nominal = $(this).data('nominal');

                $('#modal-pelanggan').text(pelanggan);
                $('#modal-nominal').text(nominal);

                var url = "{{ url('withdraws') }}/" + id + "/approve";
                $('#approvalForm').attr('action', url);
            });
        });
    </script>
@endpush
