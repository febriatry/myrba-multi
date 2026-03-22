@extends('layouts.app')

@section('title', __('Session WA'))

@section('content')
    <style>
        .form-switch {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
        }

        .form-check-input:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .form-check-label {
            font-weight: normal;
        }
    </style>

    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Session WA') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Berikut adalah daftar semua Session WA.') }}
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Session WA') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            @can('wa session create')
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#createWaBlastModal">
                        <i class="fas fa-plus"></i>
                        {{ __('Tambah WA Session') }}
                    </button>
                </div>
            @endcan

            <div class="row">

                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive p-1">
                                <table class="table table-striped datatables-basic" id="data-table" width="100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>{{ __('Session Name') }}</th>
                                            <th>{{ __('Whatsapp Number') }}</th>
                                            <th>{{ __('Api Key') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Is Aktif') }}</th>
                                            <th>{{ __('Aksi') }}</th>
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

    <!-- Modal -->
    <div class="modal fade" id="createWaBlastModal" tabindex="-1" aria-labelledby="createWaBlastModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('wa-blast.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createWaBlastModalLabel">{{ __('Tambah data WA Blast') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="session_name" class="form-label">{{ __('Session Name') }}</label>
                            <input type="text" name="session_name" id="session_name" class="form-control"
                                placeholder="{{ __('Enter session name') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.12.0/datatables.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/combine/npm/datatables.net@1.12.0,npm/datatables.net-bs5@1.12.0"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Initialize Toastr
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 5000
        };

        $(document).ready(function() {
            @if (session('success'))
                toastr.success("{{ session('success') }}", "Success");
            @endif

            @if (session('error'))
                toastr.error("{{ session('error') }}", "Error");
            @endif
        });
    </script>
    <script>
        $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 100,
            ajax: "{{ route('wa-blast.index') }}",
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'session_name',
                    name: 'session_name'
                },
                {
                    data: 'whatsapp_number',
                    name: 'whatsapp_number'
                },
                {
                    data: 'api_key',
                    name: 'api_key'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'is_aktif',
                    name: 'is_aktif',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return data;
                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
        });

        // Handle switch toggle
        $(document).on('change', '.toggle-aktif', function() {
            let id = $(this).data('id');
            let isAktif = $(this).is(':checked') ? 'Yes' : 'No';

            $.ajax({
                url: "{{ route('wa-blast.update-aktif') }}",
                method: 'POST',
                data: {
                    id: id,
                    is_aktif: isAktif,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        $('#data-table').DataTable().ajax.reload(null, false);
                        toastr.success('Status updated successfully', 'Success');
                    }
                },
                error: function(xhr) {
                    $('#data-table').DataTable().ajax.reload(null, false);
                    toastr.error('Error updating status', 'Error');
                }
            });
        });
    </script>
@endpush
